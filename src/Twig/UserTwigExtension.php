<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use App\Entity\User;

class UserTwigExtension
{
    #[AsTwigFilter('user_avatar')]
    public function getUserAvatarUrl(User $user): string
    {
        return 'https://www.gravatar.com/avatar/' . $user->getGravatarHash() . '?d=mp';
    }
}
