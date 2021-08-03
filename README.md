<!-- <?php /* -->
# PHP DOM Wrapper
**Tip:** Rename this `md` file to `php` to test it on your web server.
<!-- */ ?> -->

    <?php

    require 'src/Dom.php';

    $str = '<!DOCTYPE html>
                <head>
                    <title>Home</title>
                    <style>
                    body { border: 1px solid #c0c0c0; }
                    .nav, .item { border-top: 1px solid #c0c0c0; }
                    .center { text-align: center; }
                    .small { font-size: small; color: red; }
                    </style>
                </head>
                <body>
                    <div class="content header">
                        <h1>Home</h1>
                        <p>Default Page</p>
                    </div>
                    <div class="content main">
                        <div class="item" id="item">
                            <h2 id="title">Item Title</h2>
                            <p class="small" id="description">Item Description</a>
                            <p id="content">Item Content</p>
                        </div>
                    </div>
                    <div class="content nav">
                        <div class="center">
                            <a id="navpage" href="?">Next Page</a>
                        <div/>
                    </div>
                </body>
            </html>';

### Load the HTML
    // This can be an HTML string or a local path of the HTML file, e.g. /path/template.html
    $html = new Nggit\PHPDOMWrapper\Dom($str);

    switch ($_SERVER['QUERY_STRING']) {
        default:
## Templating
### Set attribute with attr(), using find() and the #navpage (id="navpage") as a selector
            $html->find('#navpage')->attr('href', '?page-1');
### Render the HTML
            // Stop modifying and just display the results with render():
            $html->render();
            // If you need to store the results to a variable:
            // $var = $html->output();
            break;
        case 'page-1':
### Put data to #item (id="item"), data will be assigned to its child elements that have ids related to array keys
            $data                   = array();
            $data[0]['title']       = 'The Flash';
            /* null or undefined will cause the removal of the corresponding elements
               if they only have 3 children or less
            $data[0]['description'] = null;
            $data[0]['content']     = null;
            */
            $data[1]['title']       = 'Barry Allen';
            $data[1]['description'] = 'Flash, Metahuman (Speedster)';
            $data[1]['content']     = "My name is Barry Allen and I'm the fastest man alive.";
            $data[2]['title']       = 'Clifford Devoe';
            $data[2]['description'] = 'The Thinker, Metahuman';
            $data[2]['content']     = "You could gather every genius on every planet,
                                       and you still couldn't out-think me.<br />
                                       You may be the <i>fastest man</i> alive, Allen.
                                       I'm the <i>fastest mind</i>.";
            $data[3]['title']       = 'Eobard Thawne';
            $data[3]['description'] = 'Reverse Flash, Metahuman (Speedster)';
            $data[3]['content']     = 'I told you this before. I am always one step ahead, Flash.';
            /* Begin put data with find() */
            $html->find('#item');
            foreach ($data as $item) {
                $html->put($item);
            }
            $html->clean();
            /* End put data with clean() */
### Set inner element with text()
            $html->find('title')->text('Page 1');
### Set inner element with html()
            $html->find('.header')->html('<div class="content"><h1>Page 1</h1>
                                          <p>You are viewing Page 1</p></div>');
            $html->find('#navpage')->attr('href', '?page-2');
            $html->render();
            break;
        case 'page-2':
### Set outer element with replace()
            $html->find('.main')->replace('<div class="content"><h2>Nothing Here</h2></div>');
### Remove an element with find() and remove()
            $html->find('.header')->remove();
            $html->find('#navpage')->attr('href', '?page-3');
            $html->render();
            break;
        case 'page-3':
### Remove elements with prepare() and remove()
            $html->prepare('style', '.header', '#content', '#description')->remove();
            // Or call remove() later individually:
            // $html->remove();
### Prepending and Appending elements
            $html->find('.main')->prepend('<div class="item"><h3>Before Item</h3></div>')
                                ->append('<div class="item"><h3>After Item</h3></div>');
            $html->find('#navpage')->attr('href', '?page-4');
            $html->render();
            break;
        case 'page-4':
## Extraction
### Get the attribute value
            // Find the second p (index 1):
            $class = $html->find('p', 1)->attr('class');
### Get the inner text
            $header = $html->find('.header')->text();
### Get the inner HTML
            $nav = $html->find('.nav')->html();
## Advanced
            // Find all p (Extraction)
            // In Templating, this kind of method will also work
            foreach ($html->query("//p") as $p) {
                echo $html->element($p)->html() . '<br />';
            }
            echo '$class contains: ' . $class . '<br />
                  $header contains: ' . $header . '<br />
                  $nav contains: ' . $nav;
            break;
    }
    exit;

    ?>

And more to explore...
