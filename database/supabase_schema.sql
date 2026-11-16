-- Run this once in your Supabase Dashboard -> SQL Editor
CREATE TABLE IF NOT EXISTS public.app_store (
    id TEXT PRIMARY KEY,
    data JSONB NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Keep table private to your backend Service Role Key
ALTER TABLE public.app_store ENABLE ROW LEVEL SECURITY;

