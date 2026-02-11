<?php

namespace App\Observers;

class SyncToMainObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created($model): void
    {
        if (method_exists($model, 'syncToMain')) {
            $model->syncToMain('created');
        }
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated($model): void
    {
        if (method_exists($model, 'syncToMain')) {
            $model->syncToMain('updated');
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted($model): void
    {
        if (method_exists($model, 'syncToMain')) {
            $model->syncToMain('deleted');
        }
    }
}
