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

        $division = 1;

        if (property_exists(static::class, 'unit') && static::$unit == "mile") {
            $division = 0.000621371;
        } elseif (property_exists(static::class, 'unit') && static::$unit == "km") {
            $division = 1000;
        }

        $longitude = $location->getLng() ?? null;
        $latitude = $location->getLat() ?? null;

        $q = "ST_Distance({$this->getLocationColumn()},ST_GeomFromText('POINT({$longitude} {$latitude})',4326))/{$division}";

        return $query->selectSub($q, 'distance');
    }

    /**
     * @param Builder $query
     * @param Point $location
     * @param float $inner_radius
     * @param float $outer_radius
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
    public function scopeWhereDistance(Builder $query, Point $location = null, $operator = '>', $units = 0)
    {
        $classQuerry = $query->getQuery();

        if ($classQuerry && !$classQuerry->columns) {
            $query->select([$classQuerry->from . '.*']);
        }

        $longitude = $location->getLng() ?? null;
        $latitude = $location->getLat() ?? null;

        $q = "ST_Distance({$this->getLocationColumn()},ST_GeomFromText('POINT({$longitude} {$latitude})',4326))";

        return $query->whereRaw("$q {$operator} {$units}");
    }


    public function getLocationColumn()
    {
        $column = defined('static::LOCATION') ? static::LOCATION : 'location';

        return $this->getTable() . '.' . $column;
    }
}
