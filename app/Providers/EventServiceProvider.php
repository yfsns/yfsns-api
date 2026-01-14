<?php

/**
 * YFSNS社交网络服务系统
 *
 * Copyright (C) 2025 合肥音符信息科技有限公司
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function is_array;
use function is_bool;
use function is_string;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        if (app()->environment('local')) {
            DB::listen(function ($query): void {
                $tmp = str_replace('?', '"' . '%s' . '"', $query->sql);
                $arrBindings = [];
                foreach ($query->bindings as $binding) {
                    if (is_string($binding)) {
                        $arrBindings[] = $binding;
                    } elseif (is_bool($binding)) {
                        $arrBindings[] = $binding ? '1' : '0';
                    } elseif ($binding === null) {
                        $arrBindings[] = 'NULL';
                    } elseif (is_array($binding)) {
                        $arrBindings[] = implode(',', $binding);
                    } else {
                        $arrBindings[] = $binding;
                    }
                }
                $qry = vsprintf($tmp, $arrBindings);
                Log::debug('SQL', ['sql' => $qry, 'time' => $query->time]);
            });
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
