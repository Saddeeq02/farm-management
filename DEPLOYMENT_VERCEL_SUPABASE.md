# Deploying Blackswan Farms and General Enterprises to Vercel + Supabase

## Step 1: Set Up Supabase (Persistent Cloud Database)
1. Create a free project at **https://supabase.com**.
2. Open **SQL Editor** in your Supabase dashboard, paste the contents of `database/supabase_schema.sql`, and click **Run**.
3. Go to **Project Settings → API** and copy:
   - **Project URL** (`SUPABASE_URL`, e.g. `https://xxxx.supabase.co`)
   - **service_role secret key** (`SUPABASE_SERVICE_ROLE_KEY`) — *keep this secret, server-side only*.

---

## Step 2: Deploy to Vercel
1. Push this project folder to GitHub (or install Vercel CLI with `npm i -g vercel`).
2. Import the repository in **https://vercel.com** (or run `vercel --prod` in the project folder).
3. In **Vercel → Project Settings → Environment Variables**, add:
   - `SUPABASE_URL` = your Supabase Project URL
   - `SUPABASE_SERVICE_ROLE_KEY` = your Supabase `service_role` key
   - `APP_SECRET` = any long random string (used to sign Farm Manager login cookies)
   - `GEMINI_API_KEY` = *(optional)* your Google Gemini API key
4. Redeploy once if you added environment variables after the first build.

---

## Step 3: Sign In & Change Default Password
- Visit your Vercel URL (`https://your-app.vercel.app`).
- Sign in with the default Farm Manager account (`manager` / `password123`).
- Click **Password** in the sidebar to set your private Farm Manager password.
- All your batches, inventory, sales, fixed assets, and AI memories will now persist permanently in Supabase with **zero cold-sleep** and **no cronjob needed**.

