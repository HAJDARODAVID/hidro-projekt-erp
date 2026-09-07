<?php

namespace App\Services\HidroProjekt\HR;

use App\Models\AppParametersModel;
use App\Models\AttendanceCoOpModel;
use App\Models\AttendanceModel;
use App\Models\User;
use App\Models\WorkerModel;
use App\Services\Months;

/**
 * Class WorkHoursService.
 */
class WorkHoursService
{
    private static function getAllAttendanceWorkers(){
        $groupLeaders= User::where('type', User::USER_TYPE_GROUP_LEADER)->where('active', TRUE)->with('getWorker')->get();
        $workers = WorkerModel::where('is_worker', TRUE)->where('status',1)->get();
        $attendanceWorker=[];
        foreach ($groupLeaders as $worker) {
            $attendanceWorker[$worker->worker_id] = $worker->getWorker->firstName ." ". $worker->getWorker->lastName;
        }
        foreach ($workers as $worker) {
            $attendanceWorker[$worker->id] = $worker->firstName ." ". $worker->lastName;
        }
        return $attendanceWorker;
    }

    public static function getAllAttendanceForMonthReport($month, $planedHours, $year = 2024){
        $workers = self::getAllAttendanceWorkers();
        $attendance = AttendanceModel::whereYear('date', '=', $year)->whereMonth('date', '=', $month)->get();
        $completeAttendance=[];
        $cumulativeHours['planedHours']=0;
        $cumulativeHours['workHours']=0;
        $cumulativeHours['overTime']=0;
        $cumulativeHours['sickLeave']=0;
        $cumulativeHours['paidLeave']=0;
        $cumulativeHours['dates']=[];
        $workerCount=0;
        $daysOfTheMonth = Months::dayOfMonth($month, $year);

        foreach ($workers as $key => $worker) {
            $completeAttendance[$key]['id'] = $key;
            $completeAttendance[$key]['name'] = $worker;

            $monthlyHours=0;
            $overTimeHours = 0;
            

            foreach($daysOfTheMonth as $day){
                $attendanceInfo = $attendance->where('worker_id', $key)->where('date', $day);
                $workerAttendanceInfo = NULL;
                if(!is_null($attendanceInfo)){
                    //absence
                    foreach ($attendanceInfo as $absence) {
                        if(!is_null($absence->absence_reason)){
                            $workerAttendanceInfo = AttendanceModel::ABSENCE_REASON_SHT_TXT[$absence->absence_reason];
                            if($absence->absence_reason == AttendanceModel::ABSENCE_REASON_PAID_LEAVE){
                                $monthlyHours += 8;
                            }
                        }
                    }

                    if(is_null($workerAttendanceInfo)){
                        $workerAttendanceInfo = $attendanceInfo->sum('work_hours') == NULL ? "" : $attendanceInfo->sum('work_hours');
                        $monthlyHours += $workerAttendanceInfo == "" ? 0 : $workerAttendanceInfo;
                        if($workerAttendanceInfo != ""){
                            if($workerAttendanceInfo > 8 || date("N", strtotime($day))>5){
                                if (date("N", strtotime($day))>5) {
                                    $overTimeHours += $workerAttendanceInfo;
                                }else{
                                    $overTimeHours += $workerAttendanceInfo - 8;
                                }
                            }
                        }
                    }
                }
                $completeAttendance[$key]['attendance'][$day]=$workerAttendanceInfo;
                $dayHours=NULL;
                if(!is_int($workerAttendanceInfo)){
                    if($workerAttendanceInfo == 'GO'){
                        $dayHours = 8;
                    }
                }else{
                    $dayHours = $workerAttendanceInfo;
                }
                
                if(!isset($cumulativeHours['dates'][$day])){
                    $cumulativeHours['dates'][$day] = $dayHours;
                }else{
                    $cumulativeHours['dates'][$day] += $dayHours;
                }
            }

            $completeAttendance[$key]['monthlyHours'] = $monthlyHours;
            $completeAttendance[$key]['overTime'] = $overTimeHours;

            $completeAttendance[$key]['paidLeave'] = self::getPaidLeaveForMonthAndWorker($key, $month);
            $completeAttendance[$key]['sickLeave'] = self::getSickLeaveForMonthAndWorker($key, $month);

            $cumulativeHours['workHours'] += $monthlyHours;
            $cumulativeHours['overTime'] += $overTimeHours;

            $workerCount++;
        }

        $cumulativeHours['planedHours'] = $workerCount * $planedHours;
        $cumulativeHours['paidLeave'] += self::getPaidLeaveForMonth($month);
        $cumulativeHours['sickLeave'] += self::getSickLeaveForMonth($month);

        $completeAttendance = [
            'attendance' => $completeAttendance,
            'cumulative' => $cumulativeHours
        ];
        //dd($completeAttendance);
        return $completeAttendance;
    }

    public static function getPlanedHoursForMonth($month){
        $daysOfTheMonth = Months::dayOfMonth($month);
        $planedHours = 0;
        foreach ($daysOfTheMonth as $day) {
            if(date("N", strtotime($day)) < 6){
                $planedHours += 8; 
            }
        }
        return $planedHours;
    }

    private static function getSickLeaveForMonthAndWorker($workerId, $month){
        $sickLeaveCount = AttendanceModel::where('worker_id', $workerId)
            ->where('absence_reason', AttendanceModel::ABSENCE_REASON_SICK_LEAVE)
            ->whereMonth('date','=',$month)
            ->pluck('absence_reason')
            ->count();       
        //dd($sickLeaveCount);
        return $sickLeaveCount;
    }

    private static function getSickLeaveForMonth($month){
        $sickLeaveCount = AttendanceModel::where('absence_reason', AttendanceModel::ABSENCE_REASON_SICK_LEAVE)
            ->whereMonth('date','=',$month)
            ->pluck('absence_reason')
            ->count();       
        //dd($sickLeaveCount);
        return $sickLeaveCount;
    }

    private static function getPaidLeaveForMonthAndWorker($workerId, $month){
        $sickLeaveCount = AttendanceModel::where('worker_id', $workerId)
            ->where('absence_reason', AttendanceModel::ABSENCE_REASON_PAID_LEAVE)
            ->whereMonth('date','=',$month)
            ->pluck('absence_reason')
            ->count();       
        //dd($sickLeaveCount);
        return $sickLeaveCount;
    }

    private static function getPaidLeaveForMonth($month){
        $sickLeaveCount = AttendanceModel::where('absence_reason', AttendanceModel::ABSENCE_REASON_PAID_LEAVE)
            ->whereMonth('date','=',$month)
            ->pluck('absence_reason')
            ->count();       
        //dd($sickLeaveCount);
        return $sickLeaveCount;
    }

    public static function getAllAttendanceForMonthReportCoOp($month, $year, $daysInMonth){
        $baseWorkHourCost = (float)AppParametersModel::where('param_name_srt', 'bwh-c-o')->where('active', TRUE)->first()->value;
        $attendance = AttendanceCoOpModel::whereMonth('date', '=', $month)
            ->whereYear('date', '=', $year)
            ->where('work_hours', '!=', NULL)
            ->with('getWorkerInfo', 'getWorkerInfo.getCoOpInfo')->get();

        // Single pass: build worker info and a worker/day -> hours lookup instead of
        // re-scanning the whole $attendance collection for every worker/day combination.
        $hoursByWorkerAndDay = [];
        $workers = [];
        $workerCoOp = [];
        foreach ($attendance as $att) {
            $workerId = $att->worker_id;
            $hoursByWorkerAndDay[$workerId][$att->date] = ($hoursByWorkerAndDay[$workerId][$att->date] ?? 0) + $att->work_hours;

            if (!isset($workers[$workerId])) {
                $coOp = $att->getWorkerInfo->getCoOpInfo->name;
                $workers[$workerId] = [
                    'coOp' => $coOp,
                    'name' => $att->getWorkerInfo->firstName .' '. $att->getWorkerInfo->lastName,
                ];
                $workerCoOp[$coOp][] = $workerId;
            }
        }

        $array=[];
        $sumPerDay = array_fill_keys($daysInMonth, 0);
        foreach ($workers as $workerId => $info) {
            $coOp = $info['coOp'];
            $array[$coOp][$workerId]['id'] = $workerId;
            $array[$coOp][$workerId]['name'] = $info['name'];
            $array[$coOp][$workerId]['overall'] = 0;
            $array[$coOp][$workerId]['cost'] = 0;

            foreach ($daysInMonth as $day) {
                $attInfo = $hoursByWorkerAndDay[$workerId][$day] ?? 0;
                if ($attInfo) {
                    $array[$coOp][$workerId]['dates'][$day] = $attInfo;
                    $array[$coOp][$workerId]['overall'] += $attInfo;
                    $array[$coOp][$workerId]['cost'] += $attInfo*$baseWorkHourCost;
                    $sumPerDay[$day] += $attInfo;
                } else {
                    $array[$coOp][$workerId]['dates'][$day] = NULL;
                }
            }
        }

        foreach ($sumPerDay as $day => $sum) {
            $sumPerDay[$day] = $sum == 0 ? NULL : $sum;
        }

        $overAllCost=0;
        foreach ($array as $group) {
            foreach ($group as $key => $att) {
                $overAllCost += $att['cost'];
            }
        }

        $groups=[];
        foreach ($workerCoOp as $coOp => $workerIds) {
            foreach ($daysInMonth as $day) {
                $daySum = 0;
                foreach ($workerIds as $workerId) {
                    $daySum += $hoursByWorkerAndDay[$workerId][$day] ?? 0;
                }
                $groups[$coOp][$day] = $daySum == 0 ? NULL : $daySum;
            }
        }

        $finalArray= [
            'workerAttendance' => $array,
            'sumPerDay' => $sumPerDay,
            'overAllCost' => $overAllCost,
            'groupPerDay' => $groups,
        ];

        return $finalArray;
    }

    

}
