<?php declare(strict_types=1);

namespace SupportPal\Pollcast\Tests\Functional;

use Mockery;
use SupportPal\Pollcast\Broadcasting\Socket;
use SupportPal\Pollcast\Model\Channel;
use SupportPal\Pollcast\Model\Member;
use SupportPal\Pollcast\Tests\TestCase;

use function factory;
use function now;
use function route;

class VerifySocketIdMiddlewareTest extends TestCase
{
    public function testValidSession(): void
    {
        $channel = $this->setupChannelAndMember();

        $this->post(route('supportpal.pollcast.receive'), [
            'channels' => [$channel->name],
            'time'     => now()->toDateTimeString()
        ])
            ->assertStatus(200);
    }

    public function testInvalidSession(): void
    {
        $channel = $this->setupChannelAndMember();

        $this->app->bind(Socket::class, function () {
            $mock = Mockery::mock(Socket::class);
            $mock->shouldReceive('id')
                ->andReturnNull();

            return $mock;
        });

        $this->postAjax(route('supportpal.pollcast.receive'), [
            'channels' => [$channel->name],
            'time'     => now()->toDateTimeString()
        ])
            ->assertStatus(500);
    }

    private function setupChannelAndMember(): Channel
    {
        $socketId = 'test';
        session([ Socket::UUID => $socketId ]);

        $channel = factory(Channel::class)->create([ 'name' => 'public-channel' ]);
        factory(Member::class)->create([ 'channel_id' => $channel->id, 'socket_id' => $socketId ]);

        return $channel;
    }
}
