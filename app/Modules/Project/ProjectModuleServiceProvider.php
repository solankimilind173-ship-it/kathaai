<?php

namespace App\Modules\Project;

use Illuminate\Support\ServiceProvider;

class ProjectModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Project routes are loaded from routes/web.php (require project.php inside auth middleware group).
    }
}
