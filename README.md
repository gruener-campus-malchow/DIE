# DIE
DIE Is E3RA (Extended Education Engine REST (REpresentational State Transfer) API (Application Programming Interface)) -> DIE Is Extended Education Engine REpresentational State Transfer Application Programming Interface

Dabei ist weniger das englische Wort für Sterben nahe gelegt, das der deutsche Artikel. Es ist halt DIE Api mit der wir hoffentlich schneller Projekte am GCM entwickeln können.


## API Schemes (Entwurf)

```
      GET /items/		-> gibt liste mit allen items zurück
      GET /items/?color=red	-> gibt liste mit allen roten items zurück¹
      GET /items/42		-> gibt item #42 zurück
      GET /items/42/color	-> gibt farbe des items #42 zurück
*    HEAD *			-> wie GET, gibt aber keinen body zurück
     POST /items/		-> erstellt neues item aus dem request body, gibt id zurück
     POST /items/42		-> ändert existierendes item #42 mit daten aus dem request body²
*   PATCH /items/42		-> ändert existierendes item #42 mit daten aus dem request body²
      PUT /items/42		-> fügt item aus daten aus dem request body mit id 42 ein (ersetzt ggf. das alte)
*     PUT /items/		-> ersetzt alle items mit liste aus dem request body
   DELETE /items/42		-> löscht item #42
*  DELETE /items/		-> löscht alle items
* OPTIONS *			-> gibt http 402 mit allow header zurück
*       * /items		-> 400 Bad Request
*       * /items/42/		-> 400 Bad Request
```

\* optional \
¹ siehe nächster Abschnitt für Details
² hier sollte man sich für eine Variante entscheiden

### GET Params

| Schema | Funktion |
| --- | --- |
| ` `, `?` | keine; gesamte Liste ausgeben |
| `?color=red` | nur Gegenstände, deren Attribut `color` gleich `red` ist |
| `?color=red&type=car` | nur Gegenstände, deren Attribut `color` gleich `red` ist **und** deren `type` gleich `car` ist |
| `?color=red,blue` | nur Gegenstände, deren Attribut `color` gleich `red` **oder** gleich `blue` ist |
| `?color=red&color=blue` | nur Gegenstände, deren Attribut `color` gleich `red` **und** gleich `blue` ist (1:n, n:m) |
| `?color=red&type=car&color=blue` | nur Gegenstände, deren Attribut `color` gleich `red` **und** gleich `blue` ist **und** deren `type` gleich `car` ist |
| `?color` | nur Gegenstände, deren Attribut `color` nicht `NULL` ist |


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
