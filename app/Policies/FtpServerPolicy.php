<?php

namespace App\Policies;

use App\Models\FtpServer;
use App\Models\User;

// Editing a server's config defaults and pushing them to the box: XCL staff for
// any server, a league's own manager only for servers on their own league — never
// XCL's fleet and never another league's, checked here on top of the tenant scope.
class FtpServerPolicy
{
    public function manage(User $user, FtpServer $server): bool
    {
        if ($user->canManage()) {
            return true;
        }

        return ! $server->isXclServer() && $server->league && $user->managesLeague($server->league);
    }
}
