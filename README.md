# XCLusive Racing v2.0

Professional esports racing organization website with Supabase integration.

## Features

✅ **White Header** with topo background (Raven Store aesthetic)
✅ **Sign Up System** (Steam/PS5/Xbox)
✅ **User Profiles** with ELO ratings
✅ **Race/Events** platform selection
✅ **Partner Section** (6 logo placeholders)
✅ **Merchandise** link to Raven Store
✅ **Supabase Integration** for user data
✅ **Poppins Font** (Medium, caps, italic)

## Setup

### 1. Install Dependencies
```bash
npm install
```

### 2. Set up Supabase

1. Go to https://supabase.com
2. Create a free account
3. Create a new project
4. In the SQL Editor, run this:

```sql
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  username TEXT NOT NULL,
  country TEXT,
  platform TEXT NOT NULL,
  platform_id TEXT NOT NULL,
  team TEXT,
  elo_acc INTEGER DEFAULT 1200,
  elo_lmu INTEGER DEFAULT 1200,
  elo_iracing INTEGER DEFAULT 1200,
  created_at TIMESTAMP DEFAULT NOW()
);
```

5. Get your URL and Anon Key from Project Settings
6. Create `.env.local` file:
```
NEXT_PUBLIC_SUPABASE_URL=your_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_key
```

### 3. Run Locally
```bash
npm run dev
```

Open http://localhost:3000

## Deployment

Push to GitHub → Deploy on Vercel (same as before)

## Next Steps

- [ ] Add partner logos (replace "LOGO HERE")
- [ ] Set up ELO calculation system
- [ ] Build event management
- [ ] Connect JSON race file uploader
- [ ] Create admin dashboard

---

© 2026 XCLusive Gaming Events. The lion is born to dominate.
