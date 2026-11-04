<?php
namespace App\Policies;
use App\Quiz;
use App\User;
class QuizPolicy
{
    public function view(User $user, Quiz $quiz) { return $user->id === $quiz->user_id; }
    public function update(User $user, Quiz $quiz) { return $this->view($user, $quiz); }
    public function delete(User $user, Quiz $quiz) { return $this->view($user, $quiz); }
}
