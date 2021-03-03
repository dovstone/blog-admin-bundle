<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TimeService extends AbstractController
{
    protected $please;

    public function __construct(PleaseService $please)
    {
        $this->please = $please;
    }

    public function getMonth($dateTime = null, $type = null, $months_prefixed = null)
    {
        $dateTime = !is_null($dateTime) ? new \DateTime($dateTime) : new \DateTime();
        $monthsWithout = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
        $monthsWith = array('de janvier', 'de février', 'de mars', 'd\'avril', 'de mai', 'de juin', 'de juillet', 'd\'août', 'de septembre', 'd\'octobre', 'de novembre', 'de décembre');
        /*    $monthsWith[3] = 'd\'avril';
        $monthsWith[7] = 'd\'août';
        $monthsWith[9] = 'd\'octobre';*/
        $month = (is_null($months_prefixed)) ? $monthsWithout[intval($dateTime->format("m")) - 1] : $monthsWith[intval($dateTime->format("m")) - 1];

        if (!is_null($type) and $type == 'number') {
            $month = $dateTime->format("m");
        }

        return $month;
    }

    public function getClassicTimestamp($date, $format = null, $dateAsString = null)
    {
        $dateAsString = ($dateAsString == null) ? '' : Model::getDay($date) . ' ';
        $format == null ? $format = "d/m/Y à H:i:s" : $format;
        $timestamp = new \DateTime($date, new \DateTimeZone('UTC'));
        return ucfirst($dateAsString) . '' . $timestamp->format($format);
    }

    public function getFormat($format = "Y-m-d H:i:s", $timestamp = null)
    {
        return date($format, strtotime(is_null($timestamp) ? horodatage()->datetime : str_ireplace('/', '-', $timestamp)));
    }

    public function getFrenchDateFormatToUsFormat($date, $delimiter = '-')
    {
        $exploded = explode($delimiter, $date);
        $formatted = $exploded[2] . '-' . $exploded[1] . '-' . $exploded[0];
        return new \DateTime($formatted);
    }

    public function getFrenchDate($dateTime=null, $format = "D/d/M/Y H:i:s")
    {
        if (is_string($dateTime)) {
            $dateTime = new \DateTime($dateTime, new \DateTimeZone('UTC'));
        }
        else {
            $dateTime = is_null($dateTime) ? new \DateTime() : $dateTime;
        }
        return $this->transDateToFrench($dateTime->format($format));
    }

    public function isCorrectDateFormat($date)
    {
        $exploded = explode('-', $date);
        if (
            strlen($exploded[0]) == 4// years
             and (strlen($exploded[1]) > 0 and strlen($exploded[1]) <= 2 and $exploded[1] > 0 and $exploded[1] < 13) // month
             and (strlen($exploded[2]) > 0 and strlen($exploded[2]) <= 2 and $exploded[2] > 0 and $exploded[2] < 32) // day
        ) {
            return $date;
        }
        return false;
    }

    public function getHumanDiff($timestamp, $tokens = null)
    {
        if (!is_string($timestamp)) {
            $timestamp = $timestamp->format('Y-m-d H:i:s');
        }
        $timestamp = time() - strtotime($timestamp); // to get the time since that moment
        $timestamp = ($timestamp < 1) ? 1 : $timestamp;
        $tokens = array(
            31536000 => 'an', //year
            2592000 => 'mois', //month
            604800 => 'semaine', //week
            86400 => 'jour', //day
            3600 => 'heure', //hour
            60 => 'minute', //minute
            1 => 'seconde', //second
        );

        foreach ($tokens as $unit => $text) {
            if ($timestamp < $unit) {
                continue;
            }

            $numberOfUnits = floor($timestamp / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? $text !== 'mois' ? 's' : '' : '');
        }
        return '';
    }

    public function getTimeAgo($timestamp, $tokens = null)
    {
        return $this->getHumanDiff($timestamp, $tokens);
    }

    public function getTimeRemaining($future_date, $format = "%d jours, %h heures, %i minutes, %s secondes")
    {
        $now = new \DateTime();
        if (is_string($future_date)) {
            $future_date = new \DateTime($future_date);
        }
        $interval = $future_date->diff($now);
        return $interval->format($format);
    }

    // public function getTimestamp($datetime = null)
    // {
    //     $DateTime = new \DateTime(is_null($datetime) ? date("Y-m-d H:i:s") : $datetime, new \DateTimeZone('GMT'));
    //
    //     dump($DateTime);die;
    //
    //     $horodatage = (object) [];
    //     foreach ($DateTime as $key => $value) {
    //         $horodatage->datetime = substr($DateTime->date, 0, 19);
    //         $horodatage->timezone_type = $DateTime->timezone_type;
    //         $horodatage->timezone = $DateTime->timezone;
    //     }
    //     return $horodatage;
    // }

    public function getDateTime($datetime = null)
    {
        return new \DateTime(is_null($datetime) ? date("Y-m-d H:i:s") : $datetime, new \DateTimeZone('GMT'));
    }

    public function getSelectDays($label = 'Jour')
    {
        $options = '<option value="">' . $label . '</option>';
        for ($i = 1; $i < 32; $i++) {
            $ii = strlen($i) == 1 ? '0' . $i : $i;
            $options .= '<option value="' . $ii . '">' . $i . '</option>';
        }
        return $options;
    }

    public function getSelectMonths($label = 'Mois', $short = null)
    {
        return is_null($short)
        ? '<option value="" style="color:#000;font-weight:bold">' . $label . '</option>
			<option value="01">jan</option>
			<option value="02">fév</option>
			<option value="03">mar</option>
			<option value="04">avr</option>
			<option value="05">mai</option>
			<option value="06">jui</option>
			<option value="07">Jui</option>
			<option value="08">aoû</option>
			<option value="09">sep</option>
			<option value="10">oct</option>
			<option value="11">nov</option>
			<option value="12">déc</option>'
        : '<option value="" style="color:#000;font-weight:bold">' . $label . '</option>
			<option value="01">Janvier</option>
			<option value="02">Février</option>
			<option value="03">Mars</option>
			<option value="04">Avril</option>
			<option value="05">Mai</option>
			<option value="06">Juin</option>
			<option value="07">Juillet</option>
			<option value="08">Août</option>
			<option value="09">Septembre</option>
			<option value="10">Octobre</option>
			<option value="11">Novembre</option>
			<option value="12">Décembre</option>';
    }

    public function getSelectYears($label = 'Années', $from = '1950')
    {
        $options = '<option value="" style="color:#000;font-weight:bold">' . $label . '</option>';
        for ($i = $from; $i < $this->years() + 1; $i++) {
            $options .= '<option value="' . $i . '">' . $i . '</option>';
        }
        return $options;
    }

    public function convertToHoursMins($time, $format = '%02dh%02dmin')
    {
        //echo convertToHoursMins(250, '%02d hours %02d minutes'); // should output 4 hours 17 minutes

        if ($time < 1) {
            return;
        }
        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return $hours == 0 ? sprintf('%02dmin', $minutes) : sprintf($format, $hours, $minutes);
    }

    public function getYearsOld($birthdate)
    {
        if( is_null($birthdate) ){
            return '?';
        }
        if (!is_string($birthdate)) {
            $birthdate = $birthdate->format('Y-m-d');
        }
        //$dateOfBirth = "17-10-1985";
        $today = date("Y-m-d");
        $diff = date_diff(date_create($birthdate), date_create($today));
        return $diff->format('%y');
    }

    private function transDateToFrench($date)
    {
        $xploded = explode('/', $date);
        $arr = [
            'Mon' => 'lundi',
            'Tue' => 'mardi',
            'Wed' => 'mercredi',
            'Thu' => 'jeudi',
            'Fri' => 'vendredi',
            'Sat' => 'samedi',
            'Sun' => 'dimanche',

            'Jan' => 'janvier',
            'Feb' => 'février',
            'Mar' => 'mars',
            'Apr' => 'avril',
            'May' => 'mai',
            'Jun' => 'juin',
            'Jul' => 'juillet',
            'Aug' => 'aôut',
            'Sep' => 'septembre',
            'Oct' => 'octobre',
            'Nov' => 'novembre',
            'Dec' => 'décembre',
        ];

        $date = '';
        foreach ($xploded as $partial) {
            if (isset($arr[$partial])) {
                $date .= $arr[$partial] . ' ';
            } else {
                $date .= $partial . ' ';
            }
        }
        return $date;
    }
}
