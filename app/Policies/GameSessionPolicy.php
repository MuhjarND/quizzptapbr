<?php
namespace App\Policies;
use App\GameSession;
use App\User;
class GameSessionPolicy
{
    public function view(User $user, GameSession $game) { return $user->id === $game->user_id; }
    public function update(User $user, GameSession $game) { return $this->view($user, $game); }
}
