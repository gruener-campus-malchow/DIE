# DIE
DIE Is E3RA (Extended Education Engine REST (REpresentational State Transfer) API (Application Programming Interface)) -> DIE Is Extended Education Engine REpresentational State Transfer Application Programming Interface

Dabei ist weniger das englische Wort für Sterben nahe gelegt, das der deutsche Artikel. Es ist halt DIE Api mit der wir hoffentlich schneller Projekte am GCM entwickeln können.

## URL Handling

### Examples

> This list assumes that `BASE_PATH` is set to `/api/` in `config/config.php`.

| scheme | url | match |
| --- | --- | --- |
| `/` | /api/ | `[]` |
|     | /api/?lang=de | `[]` |
|     | /api/a | **no** |
|     | /api/a/ | **no** |
|     | /api/index.php | **no** |
|     | /api// | **no** |
| `/posts/` | /api/posts | `[]` |
|     | /api/posts/ | `[]` |
|     | /api/post | **no** |
| `/about` | /api/about | `[]` |
|     | /api/about/ | **no** |
| `/posts/{post_id}/` | /api/posts/42/ | `[post_id => 42]` |
|     | /api/posts/4.2/ | `[post_id => 4.2]` |
|     | /api/posts/4,2/ | `[post_id => '4,2']` |
|     | /api/posts/4.2e2/ | `[post_id => 420]` |
|     | /api/posts/hello_world/ | `[post_id =>'hello_world']` |
|     | /api/posts/hello_world | `[post_id => 'hello_world']` |
|     | /api/posts/hello_world/?lang=de | `[post_id =>'hello_world']` |
|     | /api/posts/hello_world?lang=de | `[post_id =>'hello_world']` |
|     | /api/posts// | `[post_id => '']` |
|     | /api/posts/{foo}/ | `[post_id => '%7Bfoo%7D']` |
|     | /api/posts/ | **no** |
|     | /api/posts/hello/world/ | **no** |
| `/posts/{year}/{post_id}/` | /api/posts/2022/hello_world/ | `[year => 2022, post_id =>'hello_world']` |
|     | /api/posts/2022hello_world/ | **no** |
| `/{filename}.{extension}` | /api/index.html | `[filename => 'index', extension => 'html']` |
|     | /api/style.css.map | `[filename => 'style', 'extension' => 'css.map']` |
|     | /api/index.html/ | **no** |
|     | /api/LICENSE | **no** |
