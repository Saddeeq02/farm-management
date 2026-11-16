<?php

declare(strict_types=1);

namespace App\Core;

class BladeEngine
{
    private string $viewsPath;
    private string $cachePath;
    private array $sections = [];
    private array $sectionStack = [];
    private ?string $parentLayout = null;

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    public function render(string $view, array $data = []): string
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->parentLayout = null;

        $content = $this->evaluateView($view, $data);

        while ($this->parentLayout !== null) {
            $layout = $this->parentLayout;
            $this->parentLayout = null;
            $content = $this->evaluateView($layout, $data);
        }

        return $content;
    }

    public function includeView(string $view, array $data = []): string
    {
        $savedLayout = $this->parentLayout;
        $this->parentLayout = null;
        $output = $this->evaluateView($view, $data);
        $this->parentLayout = $savedLayout;
        return $output;
    }

    private function evaluateView(string $view, array $data): string
    {
        $viewFile = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.blade.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Blade view not found: {$view} ({$viewFile})");
        }

        $compiledFile = $this->cachePath . '/' . md5($view) . '.php';
        if (!file_exists($compiledFile) || filemtime($viewFile) > filemtime($compiledFile)) {
            $raw = file_get_contents($viewFile);
            $compiled = $this->compile($raw);
            file_put_contents($compiledFile, $compiled);
        }

        extract($data, EXTR_SKIP);
        $__blade = $this;
        $__data = $data;

        ob_start();
        include $compiledFile;
        return (string) ob_get_clean();
    }

    public function extend(string $layout): void
    {
        $this->parentLayout = $layout;
    }

    public function startSection(string $name, ?string $inlineContent = null): void
    {
        if ($inlineContent !== null) {
            $this->sections[$name] = $inlineContent;
            return;
        }
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name !== null) {
            $this->sections[$name] = (string) ob_get_clean();
        }
    }

    public function yieldContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    private function compile(string $content): string
    {
        // Remove Blade comments {{-- ... --}}
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content);

        // @extends('layout')
        $content = preg_replace('/@extends\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', '<?php $__blade->extend(\'$1\'); ?>', $content);

        // @section('name', 'value') and @section('name')
        $content = preg_replace('/@section\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([\'"].*?[\'"])\s*\)/', '<?php $__blade->startSection(\'$1\', $2); ?>', $content);
        $content = preg_replace('/@section\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', '<?php $__blade->startSection(\'$1\'); ?>', $content);
        $content = str_replace('@endsection', '<?php $__blade->endSection(); ?>', $content);

        // @yield('name', 'default') and @yield('name')
        $content = preg_replace('/@yield\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([\'"].*?[\'"])\s*\)/', '<?= $__blade->yieldContent(\'$1\', $2) ?>', $content);
        $content = preg_replace('/@yield\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', '<?= $__blade->yieldContent(\'$1\') ?>', $content);

        // @include('view', [...]) and @include('view')
        $content = preg_replace(
            '/@include\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*(\[.*?\])\s*\)/s',
            '<?= $__blade->includeView(\'$1\', array_merge($__data, $2)) ?>',
            $content
        );
        $content = preg_replace(
            '/@include\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<?= $__blade->includeView(\'$1\', $__data) ?>',
            $content
        );

        // @php ... @endphp
        $content = str_replace('@php', '<?php ', $content);
        $content = str_replace('@endphp', ' ?>', $content);

        // @csrf
        $content = str_replace('@csrf', '<input type="hidden" name="_token" value="poultry_csrf_token">', $content);

        // @forelse ($items as $item) ... @empty ... @endforelse
        $content = preg_replace_callback('/@forelse\s*\((.*?)\s+as\s+(.*?)\)/s', function ($m) {
            $arr = trim($m[1]);
            $as = trim($m[2]);
            return "<?php if (!empty({$arr})): foreach ({$arr} as {$as}): ?>";
        }, $content);
        $content = str_replace('@empty', '<?php endforeach; else: ?>', $content);
        $content = str_replace('@endforelse', '<?php endif; ?>', $content);

        // Balanced-parentheses directive compiler for @if, @elseif, @foreach, @for
        $content = $this->compileBalancedDirective($content, 'if', '<?php if (%s): ?>');
        $content = $this->compileBalancedDirective($content, 'elseif', '<?php elseif (%s): ?>');
        $content = $this->compileBalancedDirective($content, 'foreach', '<?php foreach (%s): ?>');
        $content = $this->compileBalancedDirective($content, 'for', '<?php for (%s): ?>');

        $content = preg_replace('/@else\b/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif\b/', '<?php endif; ?>', $content);
        $content = preg_replace('/@endforeach\b/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/@endfor\b/', '<?php endfor; ?>', $content);

        // Raw output {!! $expr !!}
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= $1 ?>', $content);

        // Escaped output {{ $expr }}
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= htmlspecialchars((string)($1), ENT_QUOTES, \'UTF-8\') ?>', $content);

        return $content;
    }

    private function compileBalancedDirective(string $content, string $directive, string $replacementFormat): string
    {
        $pattern = '/@' . preg_quote($directive, '/') . '\s*\(/';
        $offset = 0;

        while (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $startPos = $match[0][1];
            $openParenPos = $startPos + strlen($match[0][0]) - 1;
            $depth = 0;
            $len = strlen($content);
            $inSingleQuote = false;
            $inDoubleQuote = false;
            $endPos = null;

            for ($i = $openParenPos; $i < $len; $i++) {
                $ch = $content[$i];
                $prev = $i > 0 ? $content[$i - 1] : '';

                if ($ch === "'" && !$inDoubleQuote && $prev !== '\\') {
                    $inSingleQuote = !$inSingleQuote;
                } elseif ($ch === '"' && !$inSingleQuote && $prev !== '\\') {
                    $inDoubleQuote = !$inDoubleQuote;
                } elseif (!$inSingleQuote && !$inDoubleQuote) {
                    if ($ch === '(') {
                        $depth++;
                    } elseif ($ch === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $endPos = $i;
                            break;
                        }
                    }
                }
            }

            if ($endPos === null) {
                break;
            }

            $expr = substr($content, $openParenPos + 1, $endPos - $openParenPos - 1);
            $replacement = sprintf($replacementFormat, $expr);
            $content = substr_replace($content, $replacement, $startPos, $endPos - $startPos + 1);
            $offset = $startPos + strlen($replacement);
        }

        return $content;
    }
}

