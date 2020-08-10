<?php

namespace Digitalcloud\Postgis;

use Illuminate\Database\Eloquent\Builder;
use MStaack\LaravelPostgis\Eloquent\PostgisTrait;
use MStaack\LaravelPostgis\Geometries\Point;

trait Postgis
{
    use PostgisTrait;

    /**
     * @param Builder $query
     * @param Point $location
     * @return Builder
     */
    public function scopeWithDistance(Builder $query, $location)
    {
        $classQuery = $query->getQuery();

        if ($classQuery && !$classQuery->columns) {
            $query->select([$classQuery->from . '.*']);
        }

        if ($location) {
            if ($location instanceof Point) {
                $longitude = $location->getLng();
                $latitude = $location->getLat();
            } else {
                list($longitude, $latitude) = explode(",", $location);
            }

            $division = $this->getDivisionFactor();

            $q = "ST_Distance({$this->getLocationColumn()},ST_Point({$longitude},{$latitude}))/{$division}";
        } else {
            $q = "0";
        }

        return $query->selectSub($q, 'distance');
    }

    /**
     * @param Builder $query
     * @param Point $location
     * @param float $operator
     * @param float $units
     * @return Builder
     */
    public function scopeWhereDistance(Builder $query, $location, $operator, $units)
    {
        $classQuery = $query->getQuery();

        if ($classQuery && !$classQuery->columns) {
            $query->select([$classQuery->from . '.*']);
        }

        if ($location) {
            if ($location instanceof Point) {
                $longitude = $location->getLng();
                $latitude = $location->getLat();
            } else {
                list($latitude, $longitude) = $location;
            }

            $q = "ST_Distance({$this->getLocationColumn()},ST_Point({$longitude},{$latitude}))";
        } else {
            $q = "0";
        }

        return $query->whereRaw("$q {$operator} {$units}");
    }

    private function getLocationColumn()
    {
        $column = 'location';

        if (property_exists($this, 'location') && $this->location) {
            $column = $this->location;
        }

        return $this->getTable() . '.' . $column;
    }

    private function getDivisionFactor()
    {
        $division = 1;

        if (property_exists($this, 'unit') && $this->unit == "mile") {
            $division = 0.000621371;
        } elseif (property_exists($this, 'unit') && $this->unit == "km") {
            $division = 1000;
        }

        return $division;
    }
}
