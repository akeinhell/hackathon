<?php

namespace App\Http\Controllers;

use akeinhell\Api2Gis;
use akeinhell\Exceptions\GisRequestException;
use akeinhell\RequestParams\BranchParams;
use akeinhell\RequestParams\RubricParams;
use akeinhell\RequestParams\SearchParams;
use akeinhell\Types\GeoType;
use App\Http\Requests;
use App\Models\Firms;
use Mockery\CountValidator\Exception;

class SuggestController extends Controller
{

    public function smart()
    {
        $item = [
            "id"     => 1,
            "title"  => "string",
            "photo"  => "http://placehold.it/200x200",
            "type"   => "geo_point",
            "rating" => "9.54",
            "lon"    => "55.55555",
            "lat"    => "88.88888",
        ];

        return response()->json([
            $item,
            $item,
            $item,
            $item,
            $item,
            $item,
            $item,
            $item,
            $item,
        ]);
    }

    public function address($query = null)
    {
        $query = $query ?: request('query');
        if (!$query) {
            return response('query not defined', 404);
        }

        $searchParams = new SearchParams();
        $searchParams->setType([
            GeoType::building,
            GeoType::street,
        ]);
        $searchParams
            ->setQuery($query)
            ->setFields([
                'items.geometry.selection'
            ])
            ->setRegionId(request('region', 1))
            ->setPageSize(5);
        try {
            $data = Api2Gis::call()->search($searchParams);
        } catch (GisRequestException $e) {
            $data = null;
        }

        $addressList = [];
        if ($data) {

            foreach ($data->getItems() as $item) {
                $location = null;
                $lat      = null;
                $lon      = null;
                if (preg_match('/point/i', $item->geometry->selection)) {
                    $location = preg_replace('/point\((.*?)\)/i', '$1', $item->geometry->selection);
                    list($lat, $lon) = explode(' ', $location);
                }
                if (!$location) {
                    continue;
                }
                $addressList[] = [
                    "key"      => $item->id,
                    "title"    => $item->full_name,
                    'type'     => 'address',
                    "address"  => $item->name,
                    "location" => [
                        'lat' => $lat,
                        'lon' => $lon
                    ]
                ];
            }
        }
        $companyList   = [];
        $companyParams = new BranchParams();
        $companyParams
            ->setQuery($query)
            ->setFields([
                'items.point'
            ])
            ->setRegionId(request('region', 1))
            ->setPageSize(5);

        try {
            $data = Api2Gis::call()->BranchSearch($companyParams);
        } catch (GisRequestException $e) {
            $data = null;
        }

        if ($data) {
            foreach ($data->getItems() as $item) {
                if (!$item->point) {
                    continue;
                }
                $companyList[] = [
                    "key"      => $item->id,
                    'type'     => 'company',
                    "title"    => $item->name,
                    "address"  => $item->address_name,
                    "location" => [
                        'lon' => $item->point->lon,
                        'lat' => $item->point->lat,
                    ]
                ];
            }
        }
        $return = array_merge($addressList, $companyList);

        return response()->json($return);
    }


    /**
     * @todo рубрика
     * @todo фирма
     * @todo достопримечательность
     *
     * @param bool $json
     *
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function keyword($json = true)
    {
        throw new Exception('Не реализовано!!! :-)');

        return response()->json([]);
        $query   = request('query');
        $company = [];
        $rubric  = [];


        $params = new RubricParams();
        $params
            ->setQuery($query)
            ->setRegionId(request('region', 1))
            ->setPageSize(5);

        try {
            $data = Api2Gis::call()->RubricSearch($params);
        } catch (GisRequestException $e) {
            $data = null;
        }

        if ($data) {
            foreach ($data->getItems() as $item) {
                $company[] = [
                    'key'      => $item->id,
                    'type'     => 'rubric',
                    "title"    => $item->name,
                    'address'  => null,
                    'rubric'   => null,
                    'location' => null,
                ];
            }
        }


        $result = array_merge($rubric, $company);

        return $json ? response()->json($result) : $result;
    }

    public function index()
    {
        $data = array_merge(
            $this->address(false),
            $this->keyword(false)

        );

        return response()->json($data);
    }

    public function firms()
    {
        $query = request('query');

        try {
            $data = Firms::find($query);
        } catch (GisRequestException $e) {
            $data = null;
        }

        return response()->json($data);
    }
}
