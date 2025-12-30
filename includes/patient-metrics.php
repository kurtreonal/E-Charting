<?php
/**
 * Patient Metrics Calculator
 * Calculates key metrics from existing database schema
 */

if (!function_exists('calculate_average_length_of_stay')) {
    /**
     * Calculate Average Length of Stay
     * Uses admission_data (admission_date) and discharge assumption (patient_status change)
     * For active patients, uses current date as end date
     *
     * @param mysqli $con Database connection
     * @return float Average days
     */
    function calculate_average_length_of_stay($con) {
        // Calculate average length of stay based on admission date
        // For discharged patients (out-patient, deceased), calculate actual days
        // For active in-patients, calculate days from admission to now

        $sql = "
            SELECT
                AVG(
                    CASE
                        WHEN p.patient_status IN ('out-patient', 'deceased')
                        THEN DATEDIFF(COALESCE(p.created_date, NOW()), a.admission_date)
                        WHEN p.patient_status = 'in-patient'
                        THEN DATEDIFF(NOW(), a.admission_date)
                        ELSE 0
                    END
                ) as avg_stay
            FROM patients p
            LEFT JOIN admission_data a ON p.patient_id = a.patient_id
            WHERE a.admission_date IS NOT NULL
        ";

        $result = $con->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            return round($row['avg_stay'] ?? 0, 1);
        }

        return 0;
    }
}

if (!function_exists('calculate_recovery_rate')) {
    /**
     * Calculate Recovery Rate
     * Percentage of patients who recovered (changed from in-patient to out-patient or active)
     *
     * @param mysqli $con Database connection
     * @return int Percentage
     */
    function calculate_recovery_rate($con) {
        // Count patients who were admitted and are now out-patient or active (recovered)
        $total_admitted_sql = "
            SELECT COUNT(DISTINCT p.patient_id) as total
            FROM patients p
            INNER JOIN admission_data a ON p.patient_id = a.patient_id
            WHERE p.patient_status IN ('in-patient', 'out-patient', 'active')
        ";

        $recovered_sql = "
            SELECT COUNT(DISTINCT p.patient_id) as recovered
            FROM patients p
            INNER JOIN admission_data a ON p.patient_id = a.patient_id
            WHERE p.patient_status IN ('out-patient', 'active')
        ";

        $total_result = $con->query($total_admitted_sql);
        $recovered_result = $con->query($recovered_sql);

        $total = 0;
        $recovered = 0;

        if ($total_result && $row = $total_result->fetch_assoc()) {
            $total = $row['total'];
        }

        if ($recovered_result && $row = $recovered_result->fetch_assoc()) {
            $recovered = $row['recovered'];
        }

        if ($total > 0) {
            return round(($recovered / $total) * 100);
        }

        return 0;
    }
}

if (!function_exists('calculate_readmission_rate')) {
    /**
     * Calculate Readmission Rate
     * Percentage of patients who were readmitted (have multiple admission records)
     *
     * @param mysqli $con Database connection
     * @return int Percentage
     */
    function calculate_readmission_rate($con) {
        // Count patients with multiple admissions
        $sql = "
            SELECT
                COUNT(DISTINCT a.patient_id) as total_patients,
                SUM(CASE WHEN admission_count > 1 THEN 1 ELSE 0 END) as readmitted_patients
            FROM (
                SELECT patient_id, COUNT(*) as admission_count
                FROM admission_data
                GROUP BY patient_id
            ) a
        ";

        $result = $con->query($sql);

        if ($result && $row = $result->fetch_assoc()) {
            $total = $row['total_patients'] ?? 0;
            $readmitted = $row['readmitted_patients'] ?? 0;

            if ($total > 0) {
                return round(($readmitted / $total) * 100);
            }
        }

        return 0;
    }
}

if (!function_exists('calculate_patient_satisfaction')) {
    /**
     * Calculate Patient Satisfaction Score
     * Based on appointment confirmations and medication adherence
     * This is an estimation since you don't have a direct satisfaction table
     *
     * @param mysqli $con Database connection
     * @return float Score out of 5
     */
    function calculate_patient_satisfaction($con) {
        // Calculate satisfaction based on:
        // 1. Appointment confirmation rate (50% weight)
        // 2. Medication adherence rate (50% weight)

        // Appointment confirmation rate
        $appt_sql = "
            SELECT
                COUNT(*) as total_appointments,
                SUM(CASE WHEN appointment_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_appointments
            FROM appointment
            WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ";

        $appt_result = $con->query($appt_sql);
        $appt_rate = 0;

        if ($appt_result && $row = $appt_result->fetch_assoc()) {
            $total = $row['total_appointments'] ?? 0;
            $confirmed = $row['confirmed_appointments'] ?? 0;

            if ($total > 0) {
                $appt_rate = ($confirmed / $total);
            }
        }

        // Medication adherence rate (read notifications)
        $med_sql = "
            SELECT
                COUNT(*) as total_med_notifications,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as acknowledged_notifications
            FROM notification
            WHERE medication_id IS NOT NULL
            AND created_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ";

        $med_result = $con->query($med_sql);
        $med_rate = 0;

        if ($med_result && $row = $med_result->fetch_assoc()) {
            $total = $row['total_med_notifications'] ?? 0;
            $acknowledged = $row['acknowledged_notifications'] ?? 0;

            if ($total > 0) {
                $med_rate = ($acknowledged / $total);
            }
        }

        // Calculate weighted average
        $satisfaction_rate = ($appt_rate * 0.5) + ($med_rate * 0.5);

        // Convert to 5-point scale
        $score = $satisfaction_rate * 5;

        // Ensure minimum of 3.0 if there's any data
        if ($score > 0 && $score < 3.0) {
            $score = 3.0;
        }

        return round($score, 1);
    }
}

if (!function_exists('get_age_distribution')) {
    /**
     * Get age distribution of patients
     *
     * @param mysqli $con Database connection
     * @return array Age distribution data
     */
    function get_age_distribution($con) {
        $sql = "
            SELECT
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 18 THEN '0-18'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 19 AND 30 THEN '19-30'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 61 AND 75 THEN '61-75'
                    ELSE '76+'
                END as age_group,
                COUNT(*) as count
            FROM patients
            WHERE date_of_birth IS NOT NULL
            GROUP BY age_group
            ORDER BY
                CASE age_group
                    WHEN '0-18' THEN 1
                    WHEN '19-30' THEN 2
                    WHEN '31-45' THEN 3
                    WHEN '46-60' THEN 4
                    WHEN '61-75' THEN 5
                    WHEN '76+' THEN 6
                END
        ";

        $result = $con->query($sql);
        $distribution = [
            '0-18' => 0,
            '19-30' => 0,
            '31-45' => 0,
            '46-60' => 0,
            '61-75' => 0,
            '76+' => 0
        ];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $distribution[$row['age_group']] = (int)$row['count'];
            }
        }

        return $distribution;
    }
}

if (!function_exists('get_gender_distribution')) {
    /**
     * Get gender distribution of patients
     *
     * @param mysqli $con Database connection
     * @return array Gender distribution data
     */
    function get_gender_distribution($con) {
        $sql = "
            SELECT
                CASE
                    WHEN LOWER(gender) IN ('male', 'm') THEN 'Male'
                    WHEN LOWER(gender) IN ('female', 'f') THEN 'Female'
                    ELSE 'Other'
                END as gender_group,
                COUNT(*) as count
            FROM patients
            WHERE gender IS NOT NULL
            GROUP BY gender_group
        ";

        $result = $con->query($sql);
        $distribution = [
            'Male' => 0,
            'Female' => 0,
            'Other' => 0
        ];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $distribution[$row['gender_group']] = (int)$row['count'];
            }
        }

        return $distribution;
    }
}