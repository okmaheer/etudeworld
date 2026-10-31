# Etude World – how to run the website on your computer

This is a Laravel 12 website. It runs on PHP 8.2 or newer (the PHP that comes with XAMPP works). The homepage and admin panel are built;
every other menu/footer link opens a "Coming soon" page.

## One-time setup (about 10 minutes)

(If you already ran setup.bat before the admin panel was added, just run it
again – it's safe, and it will set up the database and your admin login.)

1. You need PHP 8.2+ and Composer. You already have both (PHP from XAMPP).
2. Open this folder (D:\Work\etudeworld) and double-click **setup.bat**.
   It downloads Laravel's engine files (the `vendor` folder). Wait until it says "Done".

## Every time you want to see the site

Double-click **start.bat**, then open http://127.0.0.1:8000 in your browser.
Keep the black window open while you browse; close it to stop the site.

## Admin panel

Open http://127.0.0.1:8000/admin and log in with the email and password you
chose in setup.bat. Forgot the password, or want another admin? Double-click
**create-admin.bat** (using the same email resets its password).

- **Homepage** – website stats. Visitor numbers show "—" until Google Analytics 4 is linked.
- **Blogs** – write a blog (with the formatting editor and SEO tools); "All blogs" to edit or delete.
- **Add a CELPIP Test** / **All CELPIP Tests** – add, edit, delete tests, and open the
  Writing / Speaking / Reading / Listening pages (question builders come next).
- **Manage Paid Clients** – placeholder for now.

Uploaded blog images are saved in `public/uploads/blogs/`.
The database is the file `database/database.sqlite` – back it up now and then.

## Where to change things

| What                                   | File |
|----------------------------------------|------|
| Homepage text & sections               | resources/views/home.blade.php |
| Language cards, study methods, student results (slideshow) | app/Http/Controllers/HomeController.php |
| Menu (top bar)                          | resources/views/partials/navbar.blade.php |
| Footer links                            | resources/views/partials/footer.blade.php |
| Colours, fonts, spacing                 | public/css/site.css (colours are at the very top) |
| Pictures                                | public/images/ |
| Page addresses (URLs)                   | routes/web.php |

**Before going live:** the four students in the slideshow are samples.
Replace them with your real students' results and photos (with their permission)
in HomeController.php.
