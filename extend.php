<?php

use Flarum\Event\ConfigureMiddleware;
use Flarum\Extend;
use Flarum\Foundation\Application;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\RedirectResponse;

class FlarumExtendProvider extends ServiceProvider {
    public function register() {
        app(ViewFactory::class)->composer('flarum.forum::log-out', function (View $view) {
            $view->getFactory()->startSection('content');
            ?>
            <p><?= e(app(TranslatorInterface::class)->trans('core.views.log_out.log_out_confirmation', ['{forum}' => app(SettingsRepositoryInterface::class)->get('forum_title')])) ?></p>

            <p>
                <a href="<?= e($view->getData()['url']) ?>" class="button">
                    <?= e(app(TranslatorInterface::class)->trans('core.views.log_out.log_out_button')) ?>
                </a>
            </p>
            <script>
                document.location = document.querySelector('a').href;
            </script>
            <?php
            $view->getFactory()->appendSection();
        });
    }
}

class RedirectLogoutMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($request->getUri()->getPath() === '/logout' && $response instanceof RedirectResponse) {
            return $response->withHeader('location', 'https://oauth2.cngal.org/Account/Logout');
        }

        return $response;
    }
}

return [
    // Register extenders here to customize your forum!
    new Extend\Compat(function(Dispatcher $events, Application $app) {
        $app->register(FlarumExtendProvider::class);

        $events->listen(ConfigureMiddleware::class, function (ConfigureMiddleware $event) {
            if ($event->isForum()) {
                $event->pipe(new RedirectLogoutMiddleware());
            }
        });
    }),
];
