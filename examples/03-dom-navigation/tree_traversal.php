<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<article id="main">
    <h1>Article Title</h1>
    <section>
        <p id="intro">Introduction paragraph.</p>
        <p id="body">Body paragraph.</p>
        <p id="conclusion">Conclusion paragraph.</p>
    </section>
    <footer>Article footer.</footer>
</article>
HTML);

$article = $html->find('article', 0);
$section = $html->find('section', 0);
$intro   = $html->find('#intro', 0);

// --- has_child() / hasChildNodes() ---
section('has_child() and hasChildNodes()');
echo '  article has_child() : ' . ($article->has_child() ? 'true' : 'false') . PHP_EOL;
echo '  article hasChildNodes(): ' . ($article->hasChildNodes() ? 'true' : 'false') . PHP_EOL;

// --- children() — full list ---
section('children(): all element children of article');
$kids = $article->children();
foreach ($kids as $i => $child) {
    if ($child->nodetype === HDOM_TYPE_ELEMENT) {
        echo '  [' . $i . '] ' . $child->tag . PHP_EOL;
    }
}

// --- children($idx) ---
section('children($idx): first and last child of section');
$first = $section->children(0);
$last  = $section->children(2);
echo '  children(0): ' . $first->plaintext . PHP_EOL;
echo '  children(2): ' . $last->plaintext . PHP_EOL;

// --- first_child() / firstChild() ---
section('first_child() and firstChild() on section');
echo '  first_child() : ' . $section->first_child()->plaintext . PHP_EOL;
echo '  firstChild()  : ' . $section->firstChild()->plaintext . PHP_EOL;

// --- last_child() / lastChild() ---
section('last_child() and lastChild() on section');
echo '  last_child() : ' . $section->last_child()->plaintext . PHP_EOL;
echo '  lastChild()  : ' . $section->lastChild()->plaintext . PHP_EOL;

// --- next_sibling() / nextSibling() ---
section('next_sibling() and nextSibling() on #intro');
$next = $intro->next_sibling();
while ($next !== null && $next->nodetype !== HDOM_TYPE_ELEMENT) {
    $next = $next->next_sibling();
}
echo '  next_sibling() : ' . ($next ? $next->plaintext : 'null') . PHP_EOL;

$nextCamel = $intro->nextSibling();
while ($nextCamel !== null && $nextCamel->nodetype !== HDOM_TYPE_ELEMENT) {
    $nextCamel = $nextCamel->nextSibling();
}
echo '  nextSibling()  : ' . ($nextCamel ? $nextCamel->plaintext : 'null') . PHP_EOL;

// --- prev_sibling() / previousSibling() ---
section('prev_sibling() and previousSibling() on #conclusion');
$conclusion = $html->find('#conclusion', 0);
$prev = $conclusion->prev_sibling();
while ($prev !== null && $prev->nodetype !== HDOM_TYPE_ELEMENT) {
    $prev = $prev->prev_sibling();
}
echo '  prev_sibling()     : ' . ($prev ? $prev->plaintext : 'null') . PHP_EOL;

$prevCamel = $conclusion->previousSibling();
while ($prevCamel !== null && $prevCamel->nodetype !== HDOM_TYPE_ELEMENT) {
    $prevCamel = $prevCamel->previousSibling();
}
echo '  previousSibling()  : ' . ($prevCamel ? $prevCamel->plaintext : 'null') . PHP_EOL;

// --- parent() / parentNode() ---
section('parent() and parentNode() on #intro');
echo '  parent()->tag    : ' . $intro->parent()->tag . PHP_EOL;
echo '  parentNode()->tag: ' . $intro->parentNode()->tag . PHP_EOL;

// --- find_ancestor_tag() ---
section('find_ancestor_tag(): climb to nearest article from #intro');
$ancestor = $intro->find_ancestor_tag('article');
echo '  tag : ' . ($ancestor ? $ancestor->tag : 'not found') . PHP_EOL;
echo '  id  : ' . ($ancestor ? $ancestor->id : 'n/a') . PHP_EOL;

$html->clear();
