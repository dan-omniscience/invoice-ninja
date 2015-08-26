<?php namespace App\Ninja\Repositories;

use Auth;
use Carbon;
use Session;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Activity;
use App\Models\Task;

class TaskRepository
{
    public function find($clientPublicId = null, $filter = null)
    {
        $query = \DB::table('tasks')
                    ->leftJoin('clients', 'tasks.client_id', '=', 'clients.id')
                    ->leftJoin('contacts', 'contacts.client_id', '=', 'clients.id')
                    ->leftJoin('invoices', 'invoices.id', '=', 'tasks.invoice_id')
                    ->where('tasks.account_id', '=', Auth::user()->account_id)
                    ->where(function ($query) {
                        $query->where('contacts.is_primary', '=', true)
                                ->orWhere('contacts.is_primary', '=', null);
                    })
                    ->where('contacts.deleted_at', '=', null)
                    ->where('clients.deleted_at', '=', null)
                    ->select('tasks.public_id', 'clients.name as client_name', 'clients.public_id as client_public_id', 'contacts.first_name', 'contacts.email', 'contacts.last_name', 'invoices.invoice_status_id', 'tasks.start_time', 'tasks.description', 'tasks.duration', 'tasks.is_deleted', 'tasks.deleted_at', 'invoices.invoice_number', 'invoices.public_id as invoice_public_id', 'tasks.is_running');

        if ($clientPublicId) {
            $query->where('clients.public_id', '=', $clientPublicId);
        }

        if (!Session::get('show_trash:task')) {
            $query->where('tasks.deleted_at', '=', null);
        }

        if ($filter) {
            $query->where(function ($query) use ($filter) {
                $query->where('clients.name', 'like', '%'.$filter.'%')
                      ->orWhere('contacts.first_name', 'like', '%'.$filter.'%')
                      ->orWhere('contacts.last_name', 'like', '%'.$filter.'%')
                      ->orWhere('tasks.description', 'like', '%'.$filter.'%');
            });
        }

        return $query;
    }

    public function save($publicId, $data)
    {
        if ($publicId) {
            $task = Task::scope($publicId)->firstOrFail();
        } else {
            $task = Task::createNew();
        }

        if (isset($data['client']) && $data['client']) {
            $task->client_id = Client::getPrivateId($data['client']);
        }
        if (isset($data['description'])) {
            $task->description = trim($data['description']);
        }

        $timeLog = $task->time_log ? json_decode($task->time_log, true) : [];

        if ($data['action'] == 'start') {
            $task->start_time = Carbon::now()->toDateTimeString();
            $task->is_running = true;
            $timeLog[] = [strtotime('now'), false];
        } else if ($data['action'] == 'resume') {
            $task->break_duration = strtotime('now') - strtotime($task->start_time) + $task->duration;
            $task->resume_time = Carbon::now()->toDateTimeString();
            $task->is_running = true;
            $timeLog[] = [strtotime('now'), false];
        } else if ($data['action'] == 'stop' && $task->is_running) {
            if ($task->resume_time) {
                $task->duration = $task->duration + strtotime('now') - strtotime($task->resume_time);
                $task->resume_time = null;
            } else {
                $task->duration = strtotime('now') - strtotime($task->start_time);
            }
            $timeLog[count($timeLog)-1][1] = strtotime('now');
            $task->is_running = false;
        } else if ($data['action'] == 'save' && !$task->is_running) {
            $task->start_time = $data['start_time'];
            $task->duration = $data['duration'];
            $task->break_duration = $data['break_duration'];
        }

        $task->duration = max($task->duration, 0);
        $task->break_duration = max($task->break_duration, 0);
        $task->time_log = json_encode($timeLog);

        $task->save();

        return $task;
    }

    public function bulk($ids, $action)
    {
        $tasks = Task::withTrashed()->scope($ids)->get();

        foreach ($tasks as $task) {
            if ($action == 'restore') {
                $task->restore();

                $task->is_deleted = false;
                $task->save();
            } else {
                if ($action == 'delete') {
                    $task->is_deleted = true;
                    $task->save();
                }

                $task->delete();
            }
        }

        return count($tasks);
    }
}
