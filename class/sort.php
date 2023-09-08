<?php


class sort
{
    public function customSort($a, $b) {
        if ($a['upspeed'] == $b['upspeed']) {
            if ($a['num_leechs'] == $b['num_leechs']) {
                return $a['num_incomplete'] - $b['num_incomplete'];
            }
            return $a['num_leechs'] - $b['num_leechs'];
        }
        return $a['upspeed'] - $b['upspeed'];
    }

}