<?php

namespace App\Http\Controllers\Admin;

use App\Models\ExecuteTask;
use App\Models\Result;
use App\Models\UserQuest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Quest;
use App\Models\Task;
use App\Models\Team;
use App\Http\Controllers\Users;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;

class AdminResultController extends Controller
{
    // Просчёт результатов текущего квеста
    protected function result()
    {
        $idTeams = array();
        $idQuest = "";

        $quests = Quest::where('status', 1)->get();  // текущий квест
        if (count($quests)) {
            foreach ($quests as $quest) {
                // команды учавствующие в квесте
                $idQuest = $quest->id;
                $questTeams = Quest::find($idQuest)->teams->unique();
                foreach ($questTeams as $k) {
                    $idTeams[] .= $k->id;
                }


                foreach ($idTeams as $team) {                         // для каждой команды:
                    $idUserQ = array();                                 // участники каждой команды
                    $exTasks = array();

                    $userTeams = UserQuest::ofWhereWhere('idQuest', $idQuest, 'idTeam', $team);
                    foreach ($userTeams as $u) {
                        $idUserQ[] .= $u->id;
                    }

                    $tasks = Quest::find($idQuest)->tasks();

                    foreach ($idUserQ as $v) {                          // выполненные!!! задания для команды

                        $exTask = ExecuteTask::ofWhereWhere('idUserQuest', $v, 'i', 1);
                        foreach ($exTask as $e) {
                            foreach($tasks as $t) {
                                if (($t->id) == ($e->idTask)) {
                                    $exTasks[] .= $e->idTask;
                                }
                            }
                        }
                    }

                    $result = 0;
                    foreach ($exTasks as $val) {
                        $weight = Task::find($val)->weight;
                        $result += $weight;
                    }

                    // запись результатов в таблицу results

                    $results = Result::updateOrCreate(['idQuest' => $quest->id, 'idTeam' => $team], ['result' => $result]);
                    $results->save();
                }

            }
            return redirect()->action('Admin\AdminResultController@showResult', ['idQuest' => $idQuest]);
        } else {
            return view('Admin.startAdminka')->with(['msg' => 'Нет новых завершённых квестов']);
        }

    }

    protected function showResult($idQuest)
    {
        $resultQuests = Result::where('idQuest', $idQuest)->get();
        return view('Admin.Quest.resultQuest')->with(['results' => $resultQuests]);
    }

    protected function selectPosition($idResult)
    {
        $res = Result::find($idResult);
        if ($res->position == 0) {
            $res->position = 1;
            $res->save();
        } else {
            $res->position = 0;
            $res->save();
        }
        return redirect()->action('Admin\AdminResultController@showResult', ['idQuest' => $res->idQuest]);
    }


}