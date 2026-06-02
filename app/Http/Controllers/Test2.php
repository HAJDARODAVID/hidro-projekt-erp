<?php

namespace App\Http\Controllers;

use App\Exports\Domain\Workers\Cooperators\CoOpWorkHoursExport;
use App\Models\Application\AppConfig;
use App\Models\User\UserType;
use App\Services\Config\AttendanceStylesConfigService;
use App\Services\HidroProjekt\Domain\Api\WeatherForecastService;
use App\Services\HidroProjekt\Domain\Workers\Cooperators\CooperatorsExportWorkHoursService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Redis;

class Test2 extends Controller
{
    public function index()
    {
        // $url = "https://prognoza.hr/tri/3d_graf_i_simboli.xml";
        // $xml = simplexml_load_file($url, "SimpleXMLElement", LIBXML_NOCDATA);
        // $json = json_encode($xml);
        // $array = json_decode($json,TRUE);
        // foreach ($array['grad'] as $key => $town) {
        //     $array[$town["@attributes"]['ime']] = $town['dan'];
        //     unset($array['grad'][$key]);
        // }
        // dd($array['Varazdin']);
        $service = new WeatherForecastService;
        $data = $service->toArray()->formatArray()->town('Varazdin')->getTownData();
        dd($service->toArray()->formatArray()->town('Varazdin')->forDashboard()->getTownData());
    }

    public function newLayout()
    {
        return view('module-container');
    }

    public function changeLog()
    {
        // Get the last released tag. This command finds the most recent tag.
        $lastTag = exec('git describe --tags --abbrev=0');



        // **CORRECTED**
        // Using single quotes around the format string to prevent the bash syntax error.
        $changelog = exec("git log --pretty=format:'- %s (%h)' --no-merges");

        dd($changelog);
        return 'im in';
    }

    public function getGetRouts(Router $router)
    {
        // Get the entire collection of routes registered in the application.
        $allRoutes = $router->getRoutes();

        // Initialize an array to store the filtered GET routes.
        $getRoutes = [];

        // Iterate over the entire route collection.
        foreach ($allRoutes as $route) {
            //dd($route->methods());
            // Check if the route's methods include 'GET'.
            // The `getMethods()` method returns an array of HTTP verbs for the route.
            if (in_array('GET', $route->methods())) {
                // For each route, add key information to our array.
                $getRoutes[] = [
                    'uri'       => $route->uri,
                    'name'      => $route->getName(),
                    'action'    => $route->getActionName(),
                    'methods'   => $route->methods()[0],
                ];
            }
        }

        // Return the array of GET routes as a JSON response.
        return response()->json([
            'get_routes' => $getRoutes
        ]);
    }

    public function helperTesting()
    {
        dd(UserType::init()->getAssignableTypes('hr'));
        return UserType::init()->getAssignableTypes('hr');
    }

    public function redisKeys()
    {
        //$this->setNewAppCOnfig();
        $service = new AttendanceStylesConfigService;
        dd($service->getBackgroundColorSickLeave());
        // try {
        //     //$this->setNewAppCOnfig();
        //     $connection = Redis::connection();

        //     Redis::set('rucni-test', 'ovo je rucni tes');

        //     // Fetch all keys (may be expensive on large databases)
        //     $keys = $connection->keys('*');
        //     $data = [];

        //     // Map integer type codes (phpredis) to names if necessary
        //     $typeMap = [1 => 'string', 2 => 'list', 3 => 'set', 4 => 'zset', 5 => 'hash'];

        //     foreach ($keys as $key) {

        //         $type = $connection->type(str_replace("hp_erp_", "", $key));
        //         if (is_int($type)) {
        //             $type = $typeMap[$type] ?? 'unknown';
        //         }

        //         switch ($type) {
        //             case 'string':
        //                 $value = $connection->get(str_replace("hp_erp_", "", $key));
        //                 break;
        //             case 'list':
        //                 $value = $connection->lrange(str_replace("hp_erp_", "", $key), 0, -1);
        //                 break;
        //             case 'set':
        //                 $value = $connection->smembers(str_replace("hp_erp_", "", $key));
        //                 break;
        //             case 'zset':
        //                 $value = $connection->zrange(str_replace("hp_erp_", "", $key), 0, -1);
        //                 break;
        //             case 'hash':
        //                 $value = $connection->hgetall(str_replace("hp_erp_", "", $key));
        //                 break;
        //             default:
        //                 // Fallback: try to get as string
        //                 $value = $connection->get(str_replace("hp_erp_", "", $key));
        //         }

        //         $data[str_replace("hp_erp_", "", $key) . " - " . $key] = [
        //             'type'  => $type,
        //             'value' => $value,
        //         ];
        //     }

        //     return response()->json([
        //         'keys_count' => count($keys),
        //         'data' => $data,
        //     ]);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }

        // Redis::set('site_name', 'My Laravel App');
        // $keys = Redis::keys('*');
        // $siteName = Redis::get('site_name');
        // $output = [];
        // foreach ($keys as $key) {
        //     $output[$key] = Redis::get(str_replace("hp_erp_", "", $key)) . ' [' . $key . ']';
        // }
        // dd($siteName, $keys, $output);
    }

    private function setNewAppCOnfig()
    {
        $array = [
            10 => [
                'background-color' => '#FF7E29'
            ],
            20 => [
                'background-color' => '#2998FF'
            ],
            30 => [
                'background-color' => '#7E84F7'
            ],
        ];
        AppConfig::find(3)->update([
            'value'         => json_encode($array)
        ]);
    }
}
