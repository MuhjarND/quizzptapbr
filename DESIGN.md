---
name: Cerdas Bersama PTA Papua Barat
version: 2
character-version: 1
description: Arena belajar yang cerah, ramah, dan mudah dibaca.
colors:
  primary: '#3f5eff'
  ink: '#17234b'
  muted: '#63718c'
  background: '#f5f7fc'
  surface: '#ffffff'
  success: '#16896b'
  border: '#e7ebf4'
typography:
  sans: { fontFamily: 'Plus Jakarta Sans, sans-serif' }
  display: { fontFamily: 'Outfit, sans-serif' }
rounded:
  DEFAULT: 12px
  lg: 20px
spacing:
  page-max: 1320px
  section-gap: 28px
components:
  button: {}
  card: {}
  dialog: {}
---

Cerdas Bersama PTA Papua Barat serves Indonesian hosts on laptops and guests on phones. Dashboard/editor are product surfaces; landing is a concise brand surface. The signature is a royal-blue 3D trophy with playful quiz shapes, complemented by original illustrated quiz covers. No borrowed Kahoot identity.

The generated dashboard concept is the visual reference. Its main dashboard is implemented; the small editor/game previews establish palette only. Unsupported sample toggles from those previews are omitted. Runtime source of truth is `public/css/app.css` custom properties; this file mirrors those tokens. Outfit headings, Plus Jakarta Sans body, self-hosted fonts. Light-only design, white panels on cool gray; blue primary buttons, subtle borders, low shadows, 20px panels, 12px controls. Sidebar 238px; content max 1320px; 3 quiz columns desktop, 2 tablet, 1 phone. Mobile navigation becomes a horizontal header.

Use semantic 20px stroke icons from a shared Blade primitive. Headers 28–44px, body 14px, controls at least 14px, labels 12–14px. Answer buttons use coral, blue, gold, teal plus A–D labels. Hover is restrained; focus uses a 3px visible ring. Disabled and loading actions remain same width. Reduced motion removes movement. Shared scrollbar remains visible. Maintain local date display in Asia/Jayapura with UTC storage.

Character collection: Nala (coral fox), Bimo (blue robot), Kiko (mint frog), Luna (lilac rabbit), Oki (turquoise octopus), Momo (caramel bear). Original transparent 3D character art lives in public/images/characters. config/characters.php is canonical for stable keys, names, image filenames and pastel backing colors. public/css/characters.css adds the shared avatar, native radio tiles, selected-character preview and sound controls without changing the base theme. Join uses a two-column picker/form, stacking at 760px; the six native radio options stay in a 3×2 grid. Name, check mark and focus ring reinforce color. Avatars preserve full silhouettes with object-fit:contain in lobby, gameplay identity, rankings and saved results.

Brand v4: custom imagegen logo combines an open blue book, two learners, a cenderawasih and a gold spark. Exact wordmark reads Cerdas Bersama / PTA Papua Barat. Transparent original is public/images/cerdas-bersama-logo-original-v1.png, with optimized derivative cerdas-bersama-logo-v1.png consumed by shared partials/brand.blade.php. brand-music.css owns widths of 250px public desktop, 194px sidebar and 184px phone, with intrinsic image aspect ratio and no distortion. config('app.name') owns accessible naming, page titles and podium naming. Existing small favicon remains the simplified book symbol. App palette and typography consume app.css tokens.

Institutional lockup v2 adds the official PTA Papua Barat emblem at the left, separated from the custom logo by a quiet vertical rule. The source is https://pta-papuabarat.go.id/wp-content/uploads/2022/12/Logo-1.png, downloaded as public/images/pta-papua-barat-official.png. The website uses this source asset directly to preserve exact emblem detail. Public desktop sizes are 52px emblem / 235px custom logo with 12px gaps. Sidebar and phone use 32px / 146px with 7px gaps. Standalone cerdas-bersama-pta-lockup-v2.svg embeds both source PNGs for a portable composite with no font dependencies.

Original background composition: Langkah Ceria, 120 BPM, 16 bars/32s, plucked keys, soft pads, bass and light percussion. Local WAV is decoded once and looped only while a question is active, pausing at results and stopping at finished. Music and effects have independent gain and mute controls; effect cues briefly reduce music gain. Two compact control groups sit side by side on phones without horizontal overflow.

Round standings: question_result replaces the question surface with a dedicated white scoreboard (standings.css). Use a 36px Outfit heading, quiet question-progress label, compact correct-answer reference and optional participant feedback/reward strip. Rows show rank, original character, nickname, rank movement, round gain and total. Positive movement uses green, negative movement uses restrained rose plus explicit Naik/Turun labels; color is supplementary. Equal-height rows move from previous slots into server order while tabular totals count upward. Motion is orchestrated once per newly completed question, approximately two seconds, and never restarts on polling/refresh. Reduced-motion shows final values immediately. Counters are hidden from live announcements; an accessible final score remains available. Short coin ticks and rising/falling rank cues reuse the shared sound owner.

Podium ceremony: the final question replaces scores with a quiet trophy waiting screen. Host opens a dedicated navy stage framed by the existing white panel, with royal-blue curtains and spotlights. The signature is the actual selected character rising onto a bronze/silver/gold rostrum, in reveal order 3 → 2 → 1, controlled by the host. Only the current winner enters; previously revealed winners stay still. First place alone receives a gold crown, radiant halo, finite confetti and a longer fanfare. podium.css owns stage-specific --stage/--stage-light/--gold/--silver/--bronze tokens; shared panels, buttons and typography still consume app.css tokens. Desktop stage and phone stage preserve the 2–1–3 physical layout. No strobing, infinite motion or audio replay on refresh. Reduced motion presents the authorized winners immediately.
