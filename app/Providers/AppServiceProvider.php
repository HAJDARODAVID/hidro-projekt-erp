<?php

namespace App\Providers;

use App\Helpers\UserRightsHelper;
use App\View\Components\Ui\V2\Select;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('user_rights', function ($app) {
            return new UserRightsHelper;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::useBootstrapFour();
        Blade::directive('canEdit', function ($expression) {
            return '<?php echo ({$expression}) ? disabled : "">';
        });

        // -- Check if user has right
        Blade::directive('hasRights', function ($expression) {
            $right = 0;
            if (Session::get('user_rights')) {
                $right = in_array($expression, Session::get('user_rights')) ? TRUE : 0;
            }

            if ($right) {
                return "<?php if(TRUE): ?>";
            } else {
                return "<?php if(FALSE): ?>";
            }
        });
        Blade::directive('endHasRights', function () {
            return "<?php endif; ?>";
        });

        // -- Translator
        Blade::directive('translator', function ($expression) {
            //TODO:stavi tu jezik iz session
            return translator($expression, 'hr');
        });

        // Alias the newest version as the default 'ui-select'
        Blade::component(Select::class, 'ui-select');
    }
}
