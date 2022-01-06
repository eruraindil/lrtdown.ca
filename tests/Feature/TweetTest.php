<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TweetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // reset cache
        \Artisan::call('debug:clear');
    }

    /**
     * Test if application runs with no current outage.
     *
     * @return void
     */
    public function testTweetsNoCurrentOutage()
    {
        //setup test data
        factory(\App\Tweet::class, 10)->create();
        // reset cache
        \Artisan::call('debug:clear');

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('Is the LRT Down? No');
    }

    /**
     * Test if application runs with a current outage. (-5 mins ago)
     *
     * @return void
     */
    public function testTweetsCurrentOutage()
    {
        //setup test data
        factory(\App\Tweet::class, 10)->create();
        factory(\App\Tweet::class)->create([
            'created' => new Carbon('-5 minutes', config('app.timezone'))
        ]);
        // reset cache
        \Artisan::call('debug:clear');

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSeeText('Is the LRT Down? Yes');
    }
}
