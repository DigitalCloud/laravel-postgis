<?php

namespace Digitalcloud\Postgis;

use Illuminate\Database\Eloquent\Builder;
use Phaza\LaravelPostgis\Eloquent\PostgisTrait;
use Phaza\LaravelPostgis\Geometries\Point;

trait Postgis
{
    use PostgisTrait;

    /**
     * @param Builder $query
     * @param Point $location
     * @return Builder
     */
    public function scopeWithDistance(Builder $query, Point $location = null)
    {
        $classQuery = $query->getQuery();

        if ($classQuery && !$classQuery->columns) {
            $query->select([$classQuery->from . '.*']);
        }


        if ($location) {
            $longitude = $location->getLng();
            $latitude = $location->getLat();
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
     * @param  $inner_radius
     * @param  $outer_radius
     * @return Builder
     */
    public function scopeWithGeofence(Builder $query, Point $location = null, $inner_radius = 0, $outer_radius = 0)
    {
        $query = $this->scopeWithDistance($query, $location);

        return $query->havingRaw("distance BETWEEN {$inner_radius} AND {$outer_radius}");
    }

    /**
     * @param Builder $query
     * @param Point $location
     * @param float $operator
     * @param float $units
     * @return Builder
     */
    public function scopeWhereDistance(Builder $query, Point $location, $operator, $units)
    {
        $classQuery = $query->getQuery();

        if ($classQuery && !$classQuery->columns) {
            $query->select([$classQuery->from . '.*']);
        }

        $longitude = $location ? $location->getLng() : null;
        $latitude = $location ? $location->getLat() : null;

        if ($longitude && $latitude) {
            $q = "ST_Distance({$this->getLocationColumn()},ST_Point({$longitude},{$latitude}))";
        } else {
            $q = "0";
        }

        return $query->whereRaw("$q {$operator} {$units}");
    }

    public function getLocationColumn()
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
