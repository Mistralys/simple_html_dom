<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<div id="app">
    <header id="site-header" class="primary dark" role="banner">
        <h1>Site Title</h1>
    </header>
    <main>
        <article id="post-1" data-category="php" data-featured="true">
            <h2>First Post</h2>
            <p>Post content here.</p>
        </article>
        <article id="post-2" data-category="html">
            <h2>Second Post</h2>
            <p>More content here.</p>
        </article>
    </main>
</div>
HTML);

$header = $html->find('#site-header', 0);
$main   = $html->find('main', 0);
$post1  = $html->find('#post-1', 0);

// --- nodeName() ---
section('nodeName(): tag name of an element');
echo '  header.nodeName() : ' . $header->nodeName() . PHP_EOL;
echo '  post1.nodeName()  : ' . $post1->nodeName() . PHP_EOL;

// --- hasAttribute() / getAttribute() / getAllAttributes() ---
section('hasAttribute(), getAttribute(), getAllAttributes()');
echo '  hasAttribute("class")   : ' . ($header->hasAttribute('class') ? 'true' : 'false') . PHP_EOL;
echo '  hasAttribute("missing") : ' . ($header->hasAttribute('missing') ? 'true' : 'false') . PHP_EOL;
echo '  getAttribute("role")    : ' . $header->getAttribute('role') . PHP_EOL;
echo '  getAllAttributes() keys :';
foreach (array_keys($header->getAllAttributes()) as $attr) {
    echo ' ' . $attr;
}
echo PHP_EOL;

// --- setAttribute() ---
section('setAttribute(): add/change an attribute');
echo '  Before: data-featured = ' . ($post1->getAttribute('data-featured') ?? 'not set') . PHP_EOL;
$post1->setAttribute('data-featured', 'false');
echo '  After : data-featured = ' . $post1->getAttribute('data-featured') . PHP_EOL;

// --- removeAttribute() ---
section('removeAttribute(): delete an attribute');
$post2 = $html->find('#post-2', 0);
echo '  Before: hasAttribute("data-category") = ' . ($post2->hasAttribute('data-category') ? 'true' : 'false') . PHP_EOL;
$post2->removeAttribute('data-category');
echo '  After : hasAttribute("data-category") = ' . ($post2->hasAttribute('data-category') ? 'true' : 'false') . PHP_EOL;

// --- getElementById() ---
section('getElementById(): find first element with given id');
$found = $html->getElementById('post-1');
echo '  tag: ' . $found->tag . ', id: ' . $found->id . PHP_EOL;

// --- getElementsById() ---
section('getElementsById(): all – useful when id appears more than once');
$all = $html->getElementsById('post-1');
echo '  Count: ' . count($all) . PHP_EOL;
// Specific index variant:
$first = $html->getElementsById('post-1', 0);
echo '  idx=0: ' . ($first ? $first->tag : 'null') . PHP_EOL;

// --- getElementByTagName() ---
section('getElementByTagName(): first element with given tag');
$firstArticle = $main->getElementByTagName('article');
echo '  tag: ' . $firstArticle->tag . ', id: ' . $firstArticle->id . PHP_EOL;

// --- getElementsByTagName() ---
section('getElementsByTagName(): all elements with given tag');
$articles = $main->getElementsByTagName('article');
echo '  Count: ' . count($articles) . PHP_EOL;
foreach ($articles as $a) {
    echo '  id=' . $a->id . ' heading=' . $a->find('h2', 0)->plaintext . PHP_EOL;
}
// Indexed variant:
$second = $main->getElementsByTagName('article', 1);
echo '  idx=1: id=' . ($second ? $second->id : 'null') . PHP_EOL;

// --- parentNode() ---
section('parentNode(): camelCase alias for parent()');
echo '  post1.parentNode()->tag : ' . $post1->parentNode()->tag . PHP_EOL;

// --- hasChildNodes() / childNodes() ---
section('hasChildNodes() and childNodes()');
echo '  main.hasChildNodes()   : ' . ($main->hasChildNodes() ? 'true' : 'false') . PHP_EOL;
$children = $main->childNodes();
$elementKids = array_filter($children, fn($n) => $n->nodetype === HDOM_TYPE_ELEMENT);
echo '  main element children  : ' . count($elementKids) . PHP_EOL;
// Indexed variant:
$firstKid = $main->childNodes(0);
echo '  childNodes(0)->tag     : ' . ($firstKid ? $firstKid->tag : 'null') . PHP_EOL;

// --- firstChild() / lastChild() (camelCase delegates) ---
section('firstChild() / lastChild(): camelCase of first_child() / last_child()');
echo '  main.firstChild()->tag : ' . $main->firstChild()->tag . PHP_EOL;
echo '  main.lastChild()->tag  : ' . $main->lastChild()->tag . PHP_EOL;

$html->clear();
