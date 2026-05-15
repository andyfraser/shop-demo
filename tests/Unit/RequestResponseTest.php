<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;

class RequestResponseTest extends TestCase {
    public function testRequestCreation() {
        $query = ['q' => 'test'];
        $post = ['name' => 'John'];
        $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/path'];
        $request = new Request($query, $post, $server, [], [], []);

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/path', $request->getUri());
        $this->assertEquals('test', $request->getQuery('q'));
        $this->assertEquals('John', $request->getPost('name'));
        $this->assertTrue($request->isPost());
    }

    public function testHtmlResponse() {
        $response = new HtmlResponse('<h1>Hello</h1>', 201);
        $this->assertEquals('<h1>Hello</h1>', $response->getContent());
        // We can't easily test send() as it uses header() and echo, 
        // but we can check the status code if we add a getter.
    }

    public function testJsonResponse() {
        $data = ['ok' => true];
        $response = new JsonResponse($data);
        $this->assertEquals(json_encode($data), $response->getContent());
    }

    public function testRedirectResponse() {
        $response = new RedirectResponse('/home');
        // Check content? Usually empty for redirect.
        $this->assertEquals('', $response->getContent());
    }
}
