<?php

$(macro :unsafe) {
    __array_access_methods_benchmark()
} >> {
    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetGetRandomAccess;
    
    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetOverwriting;
    
    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetPushingWithoutGap;

    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetSetPushingWithGap;
    private /* int */ $lastIndexOfArrayAccessOffsetSetPushingWithGap = 0;
    
    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetUnsetPopping;
    private /* int */ $lastIndexOfArrayAccessOffsetUnsetPopping = 0;
    
    private /* \Vectory\VectorInterface */ $vectorForArrayAccessOffsetUnsetShifting;
    
    private function setUpArrayAccessBenchmark(): void
    {
        $this->vectorForArrayAccessOffsetGetRandomAccess = self::getInstance();
        $this->vectorForArrayAccessOffsetGetRandomAccess[10000] = $[DefaultValue];
        
        $this->vectorForArrayAccessOffsetSetOverwriting = self::getInstance();
        $this->vectorForArrayAccessOffsetSetOverwriting[10000] = $[DefaultValue];
        
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap = self::getInstance();

        $this->vectorForArrayAccessOffsetSetPushingWithGap = self::getInstance();

        $this->vectorForArrayAccessOffsetUnsetPopping = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetPopping[10000] = $[DefaultValue];
        $this->lastIndexOfArrayAccessOffsetUnsetPopping = 9999;
        
        $this->vectorForArrayAccessOffsetUnsetShifting = self::getInstance();
        $this->vectorForArrayAccessOffsetUnsetShifting[10000] = $[DefaultValue];
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetGetRandomAccess(): void
    {
        $this->vectorForArrayAccessOffsetGetRandomAccess[\mt_rand(0, 9999)];
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetOverwriting(): void
    {
        $this->vectorForArrayAccessOffsetSetOverwriting[\mt_rand(0, 9999)] = $[DefaultValue];
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithoutGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithoutGap[] = $[DefaultValue];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetSetPushingWithGap(): void
    {
        $this->vectorForArrayAccessOffsetSetPushingWithGap[
            $this->lastIndexOfArrayAccessOffsetSetPushingWithGap += 100
        ] = $[DefaultValue];
    }

    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetPopping(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetPopping[
            $this->lastIndexOfArrayAccessOffsetUnsetPopping--
        ]);
    }
    
    /**
     * @Revs(10000)
     */
    public function benchArrayAccessOffsetUnsetShifting(): void
    {
        unset($this->vectorForArrayAccessOffsetUnsetShifting[0]);
    }    
}