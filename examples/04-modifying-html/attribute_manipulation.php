<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

$html = str_get_html(<<<'HTML'
<a id="link1" href="https://example.com" class="external" target="_blank">Example</a>
<a id="link2" href="/about" class="internal">About</a>
<img id="img1" src="photo.jpg" alt="A photo" width="800" height="600">
HTML);

$link1 = $html->find('#link1', 0);
$link2 = $html->find('#link2', 0);
$img   = $html->find('#img1', 0);

// --- Legacy __get: read attribute ---
section('Legacy __get: read attributes directly as properties');
echo '  link1->href   : ' . $link1->href . PHP_EOL;
echo '  link1->class  : ' . $link1->class . PHP_EOL;
echo '  link1->target : ' . $link1->target . PHP_EOL;
echo '  img->src      : ' . $img->src . PHP_EOL;

// --- Legacy __set: write attribute ---
section('Legacy __set: assign attribute value directly');
echo '  Before link1->href : ' . $link1->href . PHP_EOL;
$link1->href = 'https://updated.example.com';
echo '  After  link1->href : ' . $link1->href . PHP_EOL;

// --- Legacy __isset: check attribute presence ---
section('Legacy __isset (isset/property_exists style): check attribute');
echo '  isset(link1->target) : ' . (isset($link1->target) ? 'true' : 'false') . PHP_EOL;
echo '  isset(link2->target) : ' . (isset($link2->target) ? 'true' : 'false') . PHP_EOL;

// --- Legacy __unset: remove attribute ---
section('Legacy __unset: remove attribute');
echo '  Before – link1->target : ' . ($link1->target ?: '(empty)') . PHP_EOL;
unset($link1->target);
echo '  After  – isset(link1->target) : ' . (isset($link1->target) ? 'true' : 'false') . PHP_EOL;

// --- camelCase hasAttribute() / getAttribute() ---
section('camelCase hasAttribute() / getAttribute()');
echo '  img.hasAttribute("alt")     : ' . ($img->hasAttribute('alt') ? 'true' : 'false') . PHP_EOL;
echo '  img.hasAttribute("missing") : ' . ($img->hasAttribute('missing') ? 'true' : 'false') . PHP_EOL;
echo '  img.getAttribute("width")   : ' . $img->getAttribute('width') . PHP_EOL;

// --- camelCase setAttribute() ---
section('camelCase setAttribute(): add and update');
echo '  Before img->alt : ' . $img->getAttribute('alt') . PHP_EOL;
$img->setAttribute('alt', 'Updated description');
echo '  After  img->alt : ' . $img->getAttribute('alt') . PHP_EOL;
$img->setAttribute('loading', 'lazy');
echo '  New    img->loading : ' . $img->getAttribute('loading') . PHP_EOL;

// --- camelCase getAllAttributes() ---
section('camelCase getAllAttributes(): full attribute map');
foreach ($img->getAllAttributes() as $name => $value) {
    echo '  ' . $name . ' = ' . $value . PHP_EOL;
}

// --- camelCase removeAttribute() ---
section('camelCase removeAttribute(): remove an attribute');
echo '  Before hasAttribute("width") : ' . ($img->hasAttribute('width') ? 'true' : 'false') . PHP_EOL;
$img->removeAttribute('width');
$img->removeAttribute('height');
echo '  After  hasAttribute("width") : ' . ($img->hasAttribute('width') ? 'true' : 'false') . PHP_EOL;
echo '  Final attribute count : ' . count($img->getAllAttributes()) . PHP_EOL;

$html->clear();
