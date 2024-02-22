<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmitLinksTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    function guest_can_submit_a_new_link() {
        // Отправляем POST-запрос на /submit с данными формы
        $response = $this->post('/submit', [
            'title' => 'Example Title',
            'url' => 'http://example.com',
            'description' => 'Example description.',
        ]);
// Проверяем, что данные успешно добавлены в базу данных
        $this->assertDatabaseHas('links', [
            'title' => 'Example Title'
        ]);
// Проверяем, что ответ имеет статус 302 (редирект)
        // и содержит заголовок Location, указывающий на /
        $response
            ->assertStatus(302)
            ->assertHeader('Location', url('/'));
// Проверяем, что страница по адресу / содержит текст 'Example Title'
        $this
            ->get('/')
            ->assertSee('Example Title');
    }

    /** @test */
    function link_is_not_created_if_validation_fails() {
        $response = $this->post('/submit');

        $response->assertSessionHasErrors(['title', 'url', 'description']);
    }

    /** @test */
    function link_is_not_created_with_an_invalid_url() {
        $this->withoutExceptionHandling();

        $cases = ['//invalid-url.com', '/invalid-url', 'foo.com'];

        foreach ($cases as $case) {
            try {
                $response = $this->post('/submit', [
                    'title' => 'Example Title',
                    'url' => $case,
                    'description' => 'Example description',
                ]);
            } catch (ValidationException $e) {
                $this->assertEquals('The url field must be a valid URL.', $e->validator->errors()->first('url')
                );
                continue;
            }

            $this->fail("The URL $case passed validation when it should have failed.");
        }
    }

    /** @test */
    function max_length_fails_when_too_long()
    {
        $this->withoutExceptionHandling();

        $title = str_repeat('a', 256);
        $description = str_repeat('a', 256);
        $url = 'http://';
        $url .= str_repeat('a', 256 - strlen($url));

        try {
            $this->post('/submit', compact('title', 'url', 'description'));
        } catch (ValidationException $e) {
            $this->assertEquals('The title field must not be greater than 255 characters.', $e->validator->errors()->first('title'));
;

            $this->assertEquals(
                'The url field must not be greater than 255 characters.',
                $e->validator->errors()->first('url')
            );

            $this->assertEquals(
                'The description field must not be greater than 255 characters.',
                $e->validator->errors()->first('description')
            );

            return;
        }
    }
    /** @test */
    function max_length_succeeds_when_under_max()
    {
        $url = 'http://';
        $url .= str_repeat('a', 255 - strlen($url));

        $data = [
            'title' => str_repeat('a', 255),
            'url' => $url,
            'description' => str_repeat('a', 255),
        ];

        $this->post('/submit', $data);

        $this->assertDatabaseHas('links', $data);

    }
}
