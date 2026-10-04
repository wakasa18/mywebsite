<?php

namespace Tests\Unit;

use CodeIgniter\HTTP\URI;
use CodeIgniter\Pager\PagerRenderer;
use CodeIgniter\Test\CIUnitTestCase;

final class PaginationViewTest extends CIUnitTestCase
{
    private function renderPager(int $page, int $total, string $query = '', string $selector = 'page', int $segment = 0): \DOMXPath
    {
        $renderer = new PagerRenderer([
            'currentPage' => $page, 'total' => $total, 'perPage' => 10,
            'pageCount' => (int) ceil($total / 10),
            'uri' => new URI('https://example.com/products/' . $page . '?' . $query),
            'pageSelector' => $selector, 'segment' => $segment,
        ]);
        return $this->document(view('pagers/full', ['pager' => $renderer], ['saveData' => false]));
    }

    private function document(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
        return new \DOMXPath($document);
    }

    private function href(\DOMXPath $xpath, string $label): string
    {
        return $xpath->evaluate('string(//a[@aria-label="' . $label . '"]/@href)');
    }

    public function testAdjacentNavigationDoesNotSkipTheNumberedWindow(): void
    {
        $xpath = $this->renderPager(6, 200);
        $this->assertStringContainsString('page=5', $this->href($xpath, 'Previous page'));
        $this->assertStringContainsString('page=7', $this->href($xpath, 'Next page'));
        $this->assertStringContainsString('page=1', $this->href($xpath, 'First page'));
        $this->assertStringContainsString('page=20', $this->href($xpath, 'Last page'));
        $this->assertSame(1, $xpath->query('//*[@aria-current="page"]')->length);
        $this->assertSame('6', $xpath->evaluate('string(//*[@aria-current="page"])'));
        $this->assertSame(5, $xpath->query('//ul[@class="pg-pages"]/li')->length);
    }

    public function testBoundariesDisableUnavailableControlsAndReportPartialLastPage(): void
    {
        $first = $this->renderPager(1, 25);
        $this->assertSame('', $this->href($first, 'Previous page'));
        $this->assertSame('', $this->href($first, 'First page'));
        $this->assertStringContainsString('page=2', $this->href($first, 'Next page'));
        $last = $this->renderPager(3, 25);
        $this->assertSame('', $this->href($last, 'Next page'));
        $this->assertSame('', $this->href($last, 'Last page'));
        $this->assertSame(2, $last->query('//*[@aria-disabled="true"]')->length);
        $this->assertStringContainsString('21–25', $last->evaluate('string(//*[@class="pg-results"])'));
    }

    public function testEmptyAndSinglePageDoNotOfferDeadNavigation(): void
    {
        foreach ([0, 7] as $total) {
            $xpath = $this->renderPager($total ? 1 : 0, $total);
            $this->assertSame(0, $xpath->query('//a')->length);
            $this->assertStringContainsString($total ? '1–7' : 'No results', $xpath->evaluate('string(//*[@class="pg-results"])'));
        }
    }

    public function testFiltersAndNamedPaginationGroupsArePreserved(): void
    {
        $xpath = $this->renderPager(3, 50, 'keyword=Vitamin%20%26%20Zinc&branch_id=2&status=active&page_other=8', 'page_products');
        parse_str(parse_url($this->href($xpath, 'Next page'), PHP_URL_QUERY), $query);
        $this->assertSame(['keyword' => 'Vitamin & Zinc', 'branch_id' => '2', 'status' => 'active', 'page_other' => '8', 'page_products' => '4'], $query);
        $segments = $this->renderPager(3, 50, 'keyword=test', 'page', 2);
        $this->assertSame('/products/4', parse_url($this->href($segments, 'Next page'), PHP_URL_PATH));
    }

    public function testConfiguredTemplatesWorkThroughThePagerService(): void
    {
        $pager = new \CodeIgniter\Pager\Pager(new \Config\Pager(), service('renderer'));
        $simple = $this->document($pager->makeLinks(2, 10, 50, 'default_simple'));
        $this->assertSame(2, $simple->query('//a')->length);
        $this->assertSame('', $this->href($simple, 'First page'));
        $full = $this->document($pager->makeLinks(2, 10, 50));
        $this->assertSame(1, $full->query('//ul[@class="pg-pages"]')->length);
        $this->assertStringContainsString('page=3', $this->href($full, 'Next page'));
    }

    public function testAllowedFilterListsRemainEffective(): void
    {
        $globals = service('superglobals');
        $original = $globals->getGetArray();
        try {
            $globals->setGetArray(['branch_id' => '2', 'status' => 'near', 'ignored' => 'value']);
            $pager = new \CodeIgniter\Pager\Pager(new \Config\Pager(), service('renderer'));
            $pager->store('default', 2, 10, 50);
            $xpath = $this->document($pager->only(['branch_id', 'status'])->links());
            parse_str(parse_url($this->href($xpath, 'Next page'), PHP_URL_QUERY), $query);
            $this->assertSame(['branch_id' => '2', 'status' => 'near', 'page' => '3'], $query);
        } finally {
            $globals->setGetArray($original);
        }
    }
}
