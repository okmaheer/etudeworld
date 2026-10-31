# EtudeWorld – Pending Tasks

Saved 14 Oct 2026. Nothing below is built yet unless it says "done". Size: S = small, M = medium, L = large.

## 1. Menu bar (navigation)

Where: `resources/views/partials/navbar.blade.php` (desktop + mobile menu), footer links in `partials/footer.blade.php`, new page routes in `routes/web.php`.

New menu structure:

- **Language Exams** > English > IELTS, CELPIP, **TOEFL** (new)
- **Vocabulary Builder**
  - IELTS: Listening, Reading, Writing, Speaking, Important Spellings for IELTS Listening
  - CELPIP: Listening, Reading, Writing, Speaking
- **Tools**: Study Planner, IELTS Overall Score Calculator, IELTS to TOEFL, IELTS to PTE, IELTS to CELPIP, IELTS to CEFR, English Level Test / CEFR Calculator

Tasks:
- [ ] N1 (S) Rebuild the menu with the two new categories and the TOEFL entry. Dropdowns on desktop, an accordion on mobile.
- [ ] N2 (S) Until a page is built, its menu item shows a "Coming soon" page (the site already has one) so no link is broken.
- [ ] N3 (S) Add the same links to the footer, and add every new page to the sitemap with its own title and description (Google search).

## 2. Vocabulary Builder

Done so far (IELTS only): admin page per test (Reading / Listening) where AI suggests 80–100 B1–C2 words and phrases with meaning, synonyms, example and 10 translations; you approve them. Listening transcripts box. Results-page card with language choice, instant PDF download and flashcards. Tables for weekly sets are created but not used yet.

Note: the admin side exists for **IELTS only**. CELPIP is not done yet, and the CELPIP results page has no vocabulary card yet.

### 2a. Menu pages for IELTS Listening and Reading
Where: new student pages `/vocabulary-builder`, `/vocabulary-builder/ielts`, `/vocabulary-builder/ielts/listening`, `/vocabulary-builder/ielts/reading`. Controller: extend `app/Http/Controllers/VocabularyController.php`.
- [ ] V1 (M) Hub page: cards for IELTS and CELPIP, each with its skills.
- [ ] V2 (M) Per-skill page: all tests that have approved words, split into Academic and General Training, with word counts and a progress bar.
- [ ] V3 (M) Per-test page: word list (filter by level and "phrases only"), language choice, flashcards, PDF button, "I know this / still learning" buttons.
- [ ] V4 (M) Weekly sets: admin screen to split a test's words into weeks (uses `vocab_sets`), student "This week" view.
- [ ] V5 (M) Exercises made automatically from approved words: match word to meaning, fill the blank, quiz. Needs no AI, so no running cost.
- [ ] V6 (M) Progress saving for logged-in students (new table `vocab_progress`): words known, weeks done, streak. Shown in the student portal.

### 2b. CELPIP Listening and Reading vocabulary
- [ ] V7 (M) Make `vocab_words` work for CELPIP: add an `exam` column and a CELPIP test id (today it only links to IELTS tests).
- [ ] V8 (M) Admin: a "Vocabulary" button on the CELPIP Listening and Reading admin pages (`admin/celpip/...`), reading the CELPIP passages and the Listening transcripts. First check how CELPIP stores its text and add a transcript box for CELPIP Listening if missing.
- [ ] V9 (S) Results page card in `public/js/celpip-reading.js` and `celpip-listening.js` (same card, PDF and flashcards).
- [ ] V10 (S) CELPIP menu pages, same as V2/V3.

### 2c. Writing and Speaking vocabulary (IELTS and CELPIP) – topic based
How: not tied to a test. You pick a topic (for example Environment, Education, Technology, Health) and the AI generates words, collocations and useful phrases for it. You review and approve, as with Listening and Reading.
Where: new admin page "Vocabulary topics" (Writing / Speaking, per exam), and new student pages under the Vocabulary Builder menu.
- [ ] V11 (M) Database: `vocab_topics` (name, exam, skill) and a topic column on words.
- [ ] V12 (M) Admin: add a topic, "Generate words", review and approve. Different AI instruction for Writing (formal and linking phrases, Task 1 trend language, Task 2 arguments, band 7+ collocations) and Speaking (spoken phrases, idioms, fillers, Part 1/2/3 answer starters).
- [ ] V13 (M) Student pages: topics list, topic page with words, example sentences, language choice, PDF, flashcards. Same exercises as V5.
- [ ] V14 (S) For CELPIP, the same with CELPIP-style topics (workplace, daily life, community).

### 2d. Important Spellings for IELTS Listening
How: when you save a Part 1 or Part 4 question in a Listening test, the answers are collected automatically, cleaned (no numbers, dates or very easy words, duplicates merged), and you tick which ones are shown.
- [ ] V15 (M) Collector: runs on save of Part 1 / Part 4 groups, stores candidate words with the number of tests they appear in.
- [ ] V16 (S) Admin review page (show / hide), including alternative spellings.
- [ ] V17 (M) Student page: grouped by topic or type, search and A–Z buttons, "appears in N tests", spelling practice (the browser reads the word aloud, the student types it). Show only words, never the question.
- [ ] Decide: free for everyone or partly paid.

## 3. Tools

Where: new group of public pages `/tools/...`, one controller `ToolsController`, one view per tool. The score calculators and converters run in the browser (no AI, no cost). The data tables live in one admin-editable place so they can be updated without code.

- [ ] T1 (S) Tools hub page listing all tools.
- [ ] T2 (S) **IELTS Overall Score Calculator**: four band boxes (Listening, Reading, Writing, Speaking), average rounded the official way (nearest half band; .25 goes up to .5, .75 goes up to the next whole band). Works for Academic and General Training.
- [ ] T3 (M) **Converters**: IELTS to TOEFL iBT, to PTE Academic, to CELPIP (CLB levels), and to CEFR. Input: overall band (and per skill for CELPIP / CLB). Output: the equivalent score or level with a clear "approximate, check with your institution" note.
  - Before building: look up the current official comparison tables (ETS, Pearson, IRCC/CLB, Cambridge/CEFR). TOEFL changed its scoring in January 2026, so the TOEFL table needs checking first.
  - One shared page design for all four converters.
- [ ] T4 (M) **English Level Test / CEFR Calculator**:
  - Admin: a question bank of multiple choice and fill-in-the-blank questions, each tagged A1 to C2, grouped into tests (new admin menu "English Level Tests", new tables).
  - Student: its own simple interface (one question at a time, progress bar, no split screen), unlike the IELTS and CELPIP pages.
  - Result: CEFR level with a short explanation, plus suggestions (the IELTS band that matches, and links to practice).
- [ ] T5 (L) **Study Planner**: one page. The student enters exam (IELTS / CELPIP), target score, current level or last result, exam date, hours per day and weak skills. The AI (your OpenAI key) writes a week-by-week plan, shown on the page and downloadable as PDF (reusing the instant PDF builder).
  - A "Make my study plan" button at the end of each test, on the results page, which fills in the form with the test's module and score.
  - Needs: rate limit and a daily cap to control AI cost; optional login to save the plan.

## 4. TOEFL (new exam)

Where: new `toefl` exam alongside IELTS and CELPIP: hub page, admin "TOEFL Tests", and four sections (Reading, Listening, Writing, Speaking).
- [ ] F1 (S) Research the current TOEFL iBT format and scoring first (it changed in 2026) and decide which parts to build.
- [ ] F2 (L) Database, admin pages, student test pages for each section, scoring.
- [ ] F3 (M) Results pages and portal entries, vocabulary builder entries (like IELTS and CELPIP), converter and menu links.
This is a whole project, so I'd do it last and in stages (Reading first).

## 5. Additional tasks I'd add

- [ ] X1 (M) **Question-type practice pages** (Listening, later Reading): a page per question type collecting all sets of that type from all tests. Admin: start and end time boxes per question group; the site plays only that part of the full audio, without cutting any file. Includes check-answers and score.
- [ ] X2 (S) Admin "Vocabulary overview" page: every test with its word count, how many are approved, and a "Fill missing translations" shortcut.
- [ ] X3 (S) AI safety: rotate the OpenAI key that was shared in chat, set a monthly spending limit, and keep keys only in `.env`.
- [ ] X4 (M) Payment gateway (needed for paid tests and lectures), then paid lectures with a chat or comment thread under each video.
- [ ] X5 (S) Mobile layout check for every new page.

## 6. Older items still open

- Confirm the IELTS Listening band chart (a standard chart is used for both formats).
- Real Google and Microsoft sign-in keys.
- Check a real large audio upload on your computer (200 MB limit in `start.bat`).
- Decide whether plain FITB and FITB-Flow in Reading should number each blank.
- TEF, GOETHE and HSK pages.
- Check the reload-reset behaviour on CELPIP in a browser.

## Suggested order

1. Menu (N1–N3), Score Calculator (T2), converters (T3) – quick, no AI cost.
2. Vocabulary Builder pages for IELTS Listening and Reading (V1–V6), then CELPIP (V7–V10).
3. Writing and Speaking topic vocabulary (V11–V14) and Important Spellings (V15–V17).
4. English Level Test (T4) and Study Planner (T5).
5. Question-type practice pages (X1).
6. TOEFL (F1–F3), payments and lectures (X4).
