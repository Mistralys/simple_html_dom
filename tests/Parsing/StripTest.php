<?php

declare(strict_types=1);

namespace Tests\Parsing;

use PHPUnit\Framework\TestCase;

/**
 * Adapted from testcase/strip_testcase.php.
 */
class StripTest extends TestCase
{
    private \simple_html_dom $dom;

    protected function setUp(): void
    {
        $this->dom = new \simple_html_dom();
    }

    protected function tearDown(): void
    {
        $this->dom->clear();
        unset($this->dom);
    }

    public function testHtmlComments(): void
    {
        $str = <<<HTML
<div class="class0" id="id0" >
    <!--
        <input type=submit name="btnG" value="go" onclick='goto("url0")'>
    -->
</div>
HTML;
        $this->dom->load($str);
        $this->assertCount(0, $this->dom->find('input'));
    }

    public function testCodeTagPreservesContent(): void
    {
        $str = <<<HTML
<div class="class0" id="id0" >
    <CODE>
        <input type=submit name="btnG" value="go" onclick='goto("url0")'>
    </CODE>
</div>
HTML;
        $this->dom->load($str);
        $this->assertCount(1, $this->dom->find('code'));
        $this->assertCount(0, $this->dom->find('input'));
    }

    public function testPreCodePreservesContent(): void
    {
        $str = <<<HTML
<PRE><CODE CLASS=Java>
    <input type=submit name="btnG" value="go" onclick='goto("url0")'>
</CODE></PRE>
HTML;
        $this->dom->load($str);
        $this->assertCount(1, $this->dom->find('pre'));
        $this->assertCount(0, $this->dom->find('input'));
    }

    public function testScriptAndStyleTags(): void
    {
        $str = <<<HTML
<script type="text/javascript" src="test.js"></script>
<script type="text/javascript" src="test.js"/>

<style type="text/css">
@import url("style.css");
</style>

<script type="text/javascript">
var foo = "bar";
</script>
HTML;
        $this->dom->load($str);
        $this->assertCount(1, $this->dom->find('style'));
        $this->assertCount(3, $this->dom->find('script'));
    }

    public function testPhpShortTags(): void
    {
        $str = <<<'HTML'
<a href="<?=h('ok')?>">hello</a>
<input type=submit name="btnG" value="<?php echoh('ok')?>">
HTML;
        $this->dom->load($str);
        $this->assertEquals("<?=h('ok')?>", $this->dom->find('a', 0)->href);
        $this->assertEquals("<?php echoh('ok')?>", $this->dom->find('input', 0)->value);
    }

    public function testNoiseStripping(): void
    {
        $str = <<<HTML
<!--
<img class="class0" id="id0" src="src0">-->
<img class="class1" id="id1" src="src1">
<!--<img class="class2" id="id2" src="src2">
-->
HTML;
        $this->dom->load($str, true, false);
        $this->assertCount(1, $this->dom->find('img'));
        $this->assertEquals($str, (string) $this->dom);
    }

    public function testComplexScriptStyleCounting(): void
    {
        // phpcs:disable Generic.Files.LineLength
        $str = <<<HTML
<script type="text/javascript" src="test1.js">ss</script>
<script type="text/javascript" src="test2.js"/>
<script type="text/javascript" src="test3.js" />
<script type="text/javascript" src="test4.js" 
/>

<script type="text/javascript" src="test5.js"/>

<style>
@import url("style1.css");
</style>

<script>
var foo = "bar";
</script>

<style type="text/css">
@import url("style2.css");
</style>

<style>
div,td,.n a,.n a:visited{color:#000}.ts td,.tc{padding:0}.ts,.tb{border-collapse:collapse}.ti,.bl{display:inline}.ti{display:inline-table}.f,.m{color:#666}.flc,a.fl{color:#77c}a,.w,.q:visited,.q:active,.q,.b a,.b a:visited,.mblink:visited{color:#00c}a:visited{color:#551a8b}a:active{color:red}.t{background:#d5ddf3;
color:#000;
padding:5px 1px 4px}.bb{border-bottom:1px solid #36c}.bt{border-top:1px solid #36c}.j{width:34em}.h{color:#36c}.i{color:#a90a08}.a{color:green}.z{display:none}div.n{margin-top:1ex}.n a,.n .i{font-size:10pt}.n .i,.b a{font-weight:bold}.b a{font-size:12pt}.std{font-size:82%}#np,#nn,.nr,#logo span,.ch{cursor:pointer;cursor:hand}.ta{padding:3px 3px 3px 5px}#tpa2,#tpa3{padding-top:9px}#gbar{float:left;height:22px;padding-left:2px}.gbh,.gb2 div{border-top:1px solid #c9d7f1;
</style>

<!-- BEGIN ADVERTPRO ADVANCED CODE BLOCK -->

<script language="JavaScript" type="text/javascript">
<!--
document.write('<SCR'+'IPT src="zone?zid=159&pid=0&random='+Math.floor(89999999*Math.random()+10000000)+'&millis='+new Date().getTime()+'" language="JavaScript" type="text/javascript"></SCR'+'IPT>');
//-->
</script>

<!-- END ADVERTPRO ADVANCED CODE BLOCK -->

<script type="text/javascript">
var foo = "bar";
</script>
HTML;
        // phpcs:enable
        $this->dom->load($str, true, false);
        $this->assertCount(8, $this->dom->find('script'));
        $this->assertCount(3, $this->dom->find('style'));
        $this->assertEquals($str, (string) $this->dom);
    }
}
