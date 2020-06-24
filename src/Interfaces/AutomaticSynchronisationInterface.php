<?php
declare(strict_types=1);

namespace TimothyDC\LightspeedRetailApi\Interfaces;

interface AutomaticSynchronisationInterface
{
    /*
     * return ['created', 'updated', 'deleted']
     */
    public static function getLightspeedRetailApiTriggerEvents(): array;

    /*
     * return ['API resource column (case sensitive)' => 'Your model column'];
     */
    public static function getLightspeedRetailResourceMapping(): array;

    /*
     * return Lightspeed Retail resource name
     */
    public function getLightspeedRetailResourceName(): string;
}
