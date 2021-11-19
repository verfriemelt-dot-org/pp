<?php

    class Customer {

        const ACTION_KM_PERSONAL_TIMETABLE = 'kmPersonalTimetable';

        const ACTION_PERSONAL_TIMETABLE = 'personalTimetable';

        const ACTION_POINT_VERIFICATION = 'pointVerification';

        const ACTION_REPORT_FOLDER = 'reportFolder';

        const ACTION_STATION_MAP_FOLDER = 'stationMapFolder';

        const ACTION_STATION_MONITOR = 'stationMonitor';

        const ACTION_SURROUNDING_POINTS = 'surroundingPoints';

        private array $default = [
            self::ACTION_KM_PERSONAL_TIMETABLE => 0,
            self::ACTION_PERSONAL_TIMETABLE    => 0,
            self::ACTION_POINT_VERIFICATION    => 0,
            self::ACTION_REPORT_FOLDER         => 0,
            self::ACTION_STATION_MAP_FOLDER    => 0,
            self::ACTION_STATION_MONITOR       => 0,
            self::ACTION_SURROUNDING_POINTS    => 0,
        ];

        private array $data = [];

        private string $name;

        public function addRequest( string $action, string $source, string $version ) {

            if ( !isset( $this->data[$source][$version] ) ) {
                $this->data[$source][$version] = $this->default;
            }

            $this->data[$source][$version][$action]++;

            return $this;
        }

        public function getSum( string $what ) {

            $sum = 0;

            foreach ( $this->data as $source ) {
                foreach ( $source as $version ) {
                    $sum += $version[$what];
                }
            }

            return $sum;
        }

        public function getName(): string {
            return $this->name;
        }

        public function setName( string $name ): static {
            $this->name = $name;
            return $this;
        }

    }
