<?php

namespace Icinga\Module\Vspheredb\MappedClass;

class HostNumericSensorInfo
{
    /**
     * The base units in which the sensor reading is specified. If rateUnits is
     * set the units of the current reading is further qualified by the
     * rateUnits. Otherwise the value returned is 'unspecified'.
     *
     * See rateUnits
     *
     * @var string
     */
    public $baseUnits;

    /**
     * The current reading of the element indicated by the sensor. The actual
     * sensor reading is obtained by multiplying the current reading by the
     * scale factor (int -> long)
     *
     * @var int
     */
    public $currentReading;

    /**
     * The health state of the of the element indicated by the sensor. This
     * property is populated only for sensors that support threshold settings
     * and for discrete sensors using control file.
     *
     * enum HostNumericSensorHealthState:
     *
     *  - green   : The sensor is operating under normal conditions
     *  - red     : The sensor is operating under critical or fatal conditions.
     *              This may directly affect the functioning of both the sensor
     *              and related components
     *  - unknown : The implementation cannot report on the current health
     *              state of the physical element
     *  - yellow  : The sensor is operating under conditions that are non-critical
     *
     * @var ElementDescription
     */
    public $healthState;

    /**
     * A unique sensor identifier
     *
     * Example: "0.7.1.48"
     *
     * Since vSphere API 6.5
     *
     * @var string
     */
    public $id;

    /**
     * The name of the physical element associated with the sensor It consists
     * of a string of the form: "description --- state/identifier".
     *
     * @var string
     */
    public $name;

    /**
     * The rate units in which the sensor reading is specified. For example if
     * the baseUnits is Volts and the rateUnits is per second the value returned
     * by the sensor are in Volts/second. If no rate applies the value returned
     * is 'none'.
     *
     * Hint: never saw anything different than 'none'
     *
     * @var string
     */
    public $rateUnits;

    /**
     * The type of the sensor. If the sensor type is set to Other the sensor
     * name can be used to further identify the type of sensor. The sensor units
     * can also be used to further implicitly determine the type of the sensor.
     *
     * enum HostNumericSensorType:
     *
     *  - battery     : Battery sensor (API 6.5+)
     *  - bios        : BIOS/firmware related sensor (API 6.5+)
     *  - cable       : cable related sensor (API 6.5+)
     *  - fan         : Fan sensor
     *  - memory      : Memory sensor (API 6.5+)
     *  - other       : Other sensor
     *  - power       : Power sensor
     *  - processor   : Processor sensor (API 6.5+)
     *  - storage     : disk/storage sensor (API 6.5+)
     *  - systemBoard : system board sensor (API 6.5+)
     *  - temperature : Temperature sensor
     *  - voltage     : Voltage Sensor
     *  - watchdog    : Watchdog related sensor (API 6.5+)
     *
     * @var string
     */
    public $sensorType;

    /**
     * Reports the ISO 8601 Timestamp when this sensor was last updated by
     * management controller if the this sensor is capable of tracking when it
     * was last updated
     *
     * Since vSphere API 6.5
     *
     * @var string
     */
    public $timeStamp;

    /**
     * The unit multiplier for the values returned by the sensor. All values
     * returned by the sensor are current reading * 10 raised to the power of
     * the UnitModifier. If no unitModifier applies the value returned is 0.
     *
     * @var int
     */
    public $unitModifier;
}
