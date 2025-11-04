<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient</title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/add-patient.css">
</head>
<body>
    <?php include "adm-nav.php" ?>
    <div class="wrapper">
        <div class="container">
            <div class="header-section">
                <h3>NURSING ADMISSION DATA</h3> <!--Centered-->
            </div>
            <form action="" class="add-patient-form">
                <div class="main-form-section">
                    <div class="datetime-wrapper">
                        <div class="form-group">
                            <label for="admission-date">Date:</label>
                            <input type="text" id="admission-date" name="admission-date" required>
                        </div>
                        <div class="form-group">
                            <label for="time">Time:</label>
                            <input type="text" id="time" name="time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <span>Mode of Arrival</span>
                        <div class="radio-group">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="wheelchair" class="item">
                                    <input type="radio" id="wheelchair" name="mode_of_arrival" value="Wheelchair" class="hidden toggle-radio"/>
                                    <label for="wheelchair" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="wheelchair" class="cbx-lbl">Wheelchair</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="stretcher" class="item">
                                    <input type="radio" id="stretcher" name="mode_of_arrival" value="Stretcher" class="hidden toggle-radio"/>
                                    <label for="stretcher" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="stretcher" class="cbx-lbl">Stretcher</label>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                    <span>Patient and Family Instructed</span>
                    <div class="radio-group">
                        <div class="checkbox-wrapper-52" style="min-width: 40px;">
                            <label for="wardset" class="item">
                                <input type="radio" id="wardset" name="instructed" value="wardset" class="hidden toggle-radio"/>
                                <label for="wardset" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="wardset" class="cbx-lbl">Ward Set</label>
                            </label>
                        </div>

                        <div class="checkbox-wrapper-52" style="min-width: 40px;">
                            <label for="medication" class="item">
                                <input type="radio" id="medication" name="instructed" value="medication" class="hidden toggle-radio"/>
                                <label for="medication" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="medication" class="cbx-lbl">Medication</label>
                            </label>
                        </div>

                        <div class="checkbox-wrapper-52" style="min-width: 40px;">
                            <label for="hospital-rules" class="item">
                                <input type="radio" id="hospital-rules" name="instructed" value="hospital-rules" class="hidden toggle-radio"/>
                                <label for="hospital-rules" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="hospital-rules" class="cbx-lbl">Hospital Rules</label>
                            </label>
                        </div>

                        <div class="checkbox-wrapper-52" style="min-width: 40px;">
                            <label for="special" class="item">
                                <input type="radio" id="special" name="instructed" value="special" class="hidden toggle-radio"/>
                                <label for="special" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="special" class="cbx-lbl">Special Procedure Next of Kin</label>
                            </label>
                        </div>
                    </div>
                </div>

                </div>
                <div class="side-form-section">
                    <div class="form-group">
                        <span>Glasses/Contact Lenses</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="glasses-yes" class="item">
                                    <input type="radio" id="glasses-yes" name="glasses_contact" value="yes" />
                                    <label for="glasses-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="glasses-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="glasses-no" class="item">
                                    <input type="radio" id="glasses-no" name="glasses_contact" value="no" />
                                    <label for="glasses-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="glasses-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Dentures</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="dentures-yes" class="item">
                                    <input type="radio" id="dentures-yes" name="dentures" value="yes" />
                                    <label for="dentures-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="dentures-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="dentures-no" class="item">
                                    <input type="radio" id="dentures-no" name="dentures" value="no" />
                                    <label for="dentures-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="dentures-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Ambulatory/Prosthesis</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="ambulatory-yes" class="item">
                                    <input type="radio" id="ambulatory-yes" name="ambulatory" value="yes"/>
                                    <label for="ambulatory-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="ambulatory-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="ambulatory-no" class="item">
                                    <input type="radio" id="ambulatory-no" name="ambulatory" value="no" />
                                    <label for="ambulatory-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="ambulatory-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Smoker</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="smoker-yes" class="item">
                                    <input type="radio" id="smoker-yes" name="smoker" value="yes"/>
                                    <label for="smoker-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="smoker-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="smoker-no" class="item">
                                    <input type="radio" id="smoker-no" name="smoker" value="no" />
                                    <label for="smoker-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="smoker-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Drinker</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="drinker-yes" class="item">
                                    <input type="radio" id="drinker-yes" name="drinker" value="yes" />
                                    <label for="drinker-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="drinker-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="drinker-no" class="item">
                                    <input type="radio" id="drinker-no" name="drinker" value="no" />
                                    <label for="drinker-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="drinker-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="header-section">
                <h3>NURSING HISTORY</h3> <!--Centered-->
            </div>
            <form action="" class="">
                <div class="">
                    <div class="history-wrapper">
                        <div class="history-input-wrapper">
                            <div class="form-group">
                                <label for="history-date">Date:</label>
                                <input type="text" id="history-date" name="history-date" required>
                            </div>
                            <div class="form-group">
                                <label for="allergies">Allergies:</label>
                                <input type="text" id="allergies" name="allergies" required>
                            </div>
                            <div class="form-group">
                                <label for="reaction">Reaction for Hospitalization: Duration of Symptoms:</label>
                                <input type="text" id="reaction" name="reaction" required>
                            </div>
                            <div class="form-group">
                                <label for="regular-medication">Regular Medication:</label>
                                <input type="text" id="regular-medication" name="regular-medication" required>
                            </div>
                            <div class="habits">
                                <div class="form-group">
                                    <label for="dietary-habits">Dietary Habits:</label>
                                    <input type="text" id="dietary-habits" name="dietary-habits" required>
                                </div>
                                <div class="form-group">
                                    <label for="elimination">Elimination Habits:</label>
                                    <input type="text" id="elimination" name="elimination" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="sleep-patterns">Sleep Patterns:</label>
                                <input type="text" id="sleep-patterns" name="sleep-patterns" required>
                            </div>
                        </div>
                        <div class="label-headers">
                            <h3 class="section-title-patient-needs">PATIENT NEEDS</h3>
                            <h3 class="section-title-radio-options">FAMILY HISTORY</h3>
                            <h3 class="section-title-others">OTHERS/RELATIONSHIP</h3>
                        </div>
            <div class="label-section">
                <div class="form-group">
                    <div class="needs-labels">

                        <!-- Personal Care -->
                        <div class="label-row">
                            <span>Personal Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="personal-care-yes" class="item">
                                            <input type="radio" id="personal-care-yes" name="personal-care" value="yes"/>
                                            <label for="personal-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="personal-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="personal-care-no" class="item">
                                            <input type="radio" id="personal-care-no" name="personal-care" value="no" />
                                            <label for="personal-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="personal-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="personal-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Ambulation -->
                        <div class="label-row">
                            <span>Ambulation</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="ambulation-yes" class="item">
                                            <input type="radio" id="ambulation-yes" name="ambulation" value="yes" />
                                            <label for="ambulation-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="ambulation-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="ambulation-no" class="item">
                                            <input type="radio" id="ambulation-no" name="ambulation" value="no"/>
                                            <label for="ambulation-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="ambulation-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="ambulation-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Communication Problem -->
                        <div class="label-row">
                            <span>Communication Problem</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="communication-yes" class="item">
                                            <input type="radio" id="communication-yes" name="communication" value="yes"/>
                                            <label for="communication-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="communication-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="communication-no" class="item">
                                            <input type="radio" id="communication-no" name="communication" value="no" />
                                            <label for="communication-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="communication-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="communication-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Isolation -->
                        <div class="label-row">
                            <span>Isolation</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="isolation-yes" class="item">
                                            <input type="radio" id="isolation-yes" name="isolation" value="yes" />
                                            <label for="isolation-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="isolation-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="isolation-no" class="item">
                                            <input type="radio" id="isolation-no" name="isolation" value="no" />
                                            <label for="isolation-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="isolation-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="isolation-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Skin Care -->
                        <div class="label-row">
                            <span>Skin Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="skin-care-yes" class="item">
                                            <input type="radio" id="skin-care-yes" name="skin-care" value="yes"/>
                                            <label for="skin-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="skin-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="skin-care-no" class="item">
                                            <input type="radio" id="skin-care-no" name="skin-care" value="no"/>
                                            <label for="skin-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="skin-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="skin-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Wound Care -->
                        <div class="label-row">
                            <span>Wound Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="wound-care-yes" class="item">
                                            <input type="radio" id="wound-care-yes" name="wound-care" value="yes" />
                                            <label for="wound-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="wound-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="wound-care-no" class="item">
                                            <input type="radio" id="wound-care-no" name="wound-care" value="no"/>
                                            <label for="wound-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="wound-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="wound-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            </form>
            <div class="header-section">
                <h3>NURSING PHYSICAL ASSESSMENT</h3>
            </div>

            <form action="" class="fr">
                <div class="first-row">
                    <span>Ht:</span>
                    <div><input class="fr-input1" type="text" name="height" required ></div>

                    <span>Wt:</span>
                    <div><input class="fr-input2" type="text" name="weight" required ></div>

                    <span>BP lft:</span>
                    <div><input class="fr-input3" type="text" name="BP_lft" required></div>

                    <span>Pulse:</span>
                    <div><input class="fr-input4" type="text" name="pulse" required ></div>

                    <span>Strong:</span>
                    <div><input class="fr-input5" type="text" name="strong" required></div>

                    <div class="radio-cell">
                        <div class="checkbox-wrapper-52" id="left" style="margin-right: 30px; margin-left: 2rem; min-width: 0;">
                            <label for="respiration-weak" class="item">
                                <input type="radio" id="respiration-weak" name="respiration" value="weak" class="hidden toggle-radio"/>
                                <label for="respiration-weak" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="respiration-weak" class="cbx-lbl">Weak</label>
                            </label>
                        </div>

                        <div class="checkbox-wrapper-52" id="left" style="margin-right: 30px;min-width: 0;">
                            <label for="respiration-irregular" class="item">
                                <input type="radio" id="respiration-irregular" name="respiration" value="irregular" class="hidden toggle-radio"/>
                                <label for="respiration-irregular" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="respiration-irregular" class="cbx-lbl">Irregular</label>
                            </label>
                        </div>
                    </div>
                </div>

            <div class="assessment-form">
                <table class="assessment-table">
                    <tr>
                        <th>Orientation:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="orientation-time" class="item">
                                    <input type="radio" id="orientation-time" name="orientation" value="time" class="hidden toggle-radio"/>
                                    <label for="orientation-time" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-time" class="cbx-lbl">Time</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-person" class="item">
                                    <input type="radio" id="orientation-person" name="orientation" value="person" class="hidden toggle-radio"/>
                                    <label for="orientation-person" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-person" class="cbx-lbl">Person</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-event-disoriented" class="item">
                                    <input type="radio" id="orientation-event-disoriented" name="orientation" value="event-disoriented" class="hidden toggle-radio"/>
                                    <label for="orientation-event-disoriented" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-event-disoriented" class="cbx-lbl">Event Disoriented</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-confused" class="item">
                                    <input type="radio" id="orientation-confused" name="orientation" value="confused" class="hidden toggle-radio"/>
                                    <label for="orientation-confused" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-confused" class="cbx-lbl">Confused Behavior</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Color:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="skin-normal" class="item">
                                    <input type="radio" id="skin-normal" name="skin" value="normal" class="hidden toggle-radio"/>
                                    <label for="skin-normal" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-normal" class="cbx-lbl">Normal</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-pale" class="item">
                                    <input type="radio" id="skin-pale" name="skin" value="pale" class="hidden toggle-radio"/>
                                    <label for="skin-pale" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-pale" class="cbx-lbl">Pale</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-cyanotic" class="item">
                                    <input type="radio" id="skin-cyanotic" name="skin" value="cyanotic" class="hidden toggle-radio"/>
                                    <label for="skin-cyanotic" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-cyanotic" class="cbx-lbl">Cyanotic</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-jaundiced" class="item">
                                    <input type="radio" id="skin-jaundiced" name="skin" value="jaundiced" class="hidden toggle-radio"/>
                                    <label for="skin-jaundiced" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-jaundiced" class="cbx-lbl">Jaundiced</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-dusky" class="item">
                                    <input type="radio" id="skin-dusky" name="skin" value="dusky" class="hidden toggle-radio"/>
                                    <label for="skin-dusky" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-dusky" class="cbx-lbl">Dusky</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-modified" class="item">
                                    <input type="radio" id="skin-modified" name="skin" value="modified" class="hidden toggle-radio"/>
                                    <label for="skin-modified" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-modified" class="cbx-lbl">Modified</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Turgor:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-loose" class="item">
                                    <input type="radio" id="skin-turgor-loose" name="skin-turgor" value="loose" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-loose" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-loose" class="cbx-lbl">Loose</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-tight" class="item">
                                    <input type="radio" id="skin-turgor-tight" name="skin-turgor" value="tight" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-tight" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-tight" class="cbx-lbl">Tight</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-edema" class="item">
                                    <input type="radio" id="skin-turgor-edema" name="skin-turgor" value="edema" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-edema" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-edema" class="cbx-lbl">Edema</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Temp:</th>
                        <td colspan="10">
                            <!-- repeat same pattern for: warm, dry, clammy, cool, diaphoretic, moist -->
                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-warm" class="item">
                                    <input type="radio" id="skin-temp-warm" name="skin-temp" value="warm" class="hidden toggle-radio"/>
                                    <label for="skin-temp-warm" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-warm" class="cbx-lbl">Warm</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-dry" class="item">
                                    <input type="radio" id="skin-temp-dry" name="skin-temp" value="dry" class="hidden toggle-radio"/>
                                    <label for="skin-temp-dry" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-dry" class="cbx-lbl">Dry</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-clammy" class="item">
                                    <input type="radio" id="skin-temp-clammy" name="skin-temp" value="clammy" class="hidden toggle-radio"/>
                                    <label for="skin-temp-clammy" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-clammy" class="cbx-lbl">Clammy</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-cool" class="item">
                                    <input type="radio" id="skin-temp-cool" name="skin-temp" value="cool" class="hidden toggle-radio"/>
                                    <label for="skin-temp-cool" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-cool" class="cbx-lbl">Cool</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-diaphoretic" class="item">
                                    <input type="radio" id="skin-temp-diaphoretic" name="skin-temp" value="diaphoretic" class="hidden toggle-radio"/>
                                    <label for="skin-temp-diaphoretic" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-diaphoretic" class="cbx-lbl">Diaphoretic</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-moist" class="item">
                                    <input type="radio" id="skin-temp-moist" name="skin-temp" value="moist" class="hidden toggle-radio"/>
                                    <label for="skin-temp-moist" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-moist" class="cbx-lbl">Moist</label>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Mucous Membrane:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="mucous-moist" class="item"><input type="radio" id="mucous-moist" name="mucous-membrane" value="moist" class="hidden toggle-radio"/><label for="mucous-moist" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-moist" class="cbx-lbl">Moist</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-dry" class="item"><input type="radio" id="mucous-dry" name="mucous-membrane" value="dry" class="hidden toggle-radio"/><label for="mucous-dry" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-dry" class="cbx-lbl">Dry</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-cracked" class="item"><input type="radio" id="mucous-cracked" name="mucous-membrane" value="cracked" class="hidden toggle-radio"/><label for="mucous-cracked" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-cracked" class="cbx-lbl">Cracked</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-sore" class="item"><input type="radio" id="mucous-sore" name="mucous-membrane" value="sore" class="hidden toggle-radio"/><label for="mucous-sore" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-sore" class="cbx-lbl">Sore</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Peripheral Sounds:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="peripheral-audible" class="item"><input type="radio" id="peripheral-audible" name="peripheral-sounds" value="audible" class="hidden toggle-radio"/><label for="peripheral-audible" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="peripheral-audible" class="cbx-lbl">Audible</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="peripheral-sound" class="item"><input type="radio" id="peripheral-sound" name="peripheral-sounds" value="sound" class="hidden toggle-radio"/><label for="peripheral-sound" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="peripheral-sound" class="cbx-lbl">Sound</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Neck Vein Distention a! 45:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="neck-absent" class="item"><input type="radio" id="neck-absent" name="neck-vein-distention" value="absent" class="hidden toggle-radio"/><label for="neck-absent" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="neck-absent" class="cbx-lbl">Absent</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="neck-flat" class="item"><input type="radio" id="neck-flat" name="neck-vein-distention" value="flat" class="hidden toggle-radio"/><label for="neck-flat" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="neck-flat" class="cbx-lbl">Flat</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Respiratory Status:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="resp-labored" class="item"><input type="radio" id="resp-labored" name="respiratory-status" value="labored" class="hidden toggle-radio"/><label for="resp-labored" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-labored" class="cbx-lbl">Labored</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-unlabored" class="item"><input type="radio" id="resp-unlabored" name="respiratory-status" value="unlabored" class="hidden toggle-radio"/><label for="resp-unlabored" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-unlabored" class="cbx-lbl">Unlabored</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-sob" class="item"><input type="radio" id="resp-sob" name="respiratory-status" value="sob" class="hidden toggle-radio"/><label for="resp-sob" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-sob" class="cbx-lbl">SOB</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-accessory" class="item"><input type="radio" id="resp-accessory" name="respiratory-status" value="accessory" class="hidden toggle-radio"/><label for="resp-accessory" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="resp-accessory" class="cbx-lbl">Accessory Muscles</label>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th>Respiratory Sounds:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="resp-clear" class="item"><input type="radio" id="resp-clear" name="respiratory-sounds" value="clear" class="hidden toggle-radio"/><label for="resp-clear" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-clear" class="cbx-lbl">Rules</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-rhonchi" class="item"><input type="radio" id="resp-rhonchi" name="respiratory-sounds" value="rhonchi" class="hidden toggle-radio"/><label for="resp-rhonchi" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-rhonchi" class="cbx-lbl">Bhonchi Wheezing</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-crackles" class="item"><input type="radio" id="resp-crackles" name="respiratory-sounds" value="crackles" class="hidden toggle-radio"/><label for="resp-crackles" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-crackles" class="cbx-lbl">Clear</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Cough:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="cough-none" class="item"><input type="radio" id="cough-none" name="cough" value="none" class="hidden toggle-radio"/><label for="cough-none" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-none" class="cbx-lbl">None</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="cough-productive" class="item"><input type="radio" id="cough-productive" name="cough" value="productive" class="hidden toggle-radio"/><label for="cough-productive" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-productive" class="cbx-lbl">Productive</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="cough-dry" class="item"><input type="radio" id="cough-dry" name="cough" value="dry" class="hidden toggle-radio"/><label for="cough-dry" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-dry" class="cbx-lbl">None Productive</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Sputum:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="sputum-moderate" class="item"><input type="radio" id="sputum-moderate" name="sputum" value="moderate" class="hidden toggle-radio"/><label for="sputum-moderate" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-moderate" class="cbx-lbl">Moderate</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-large" class="item"><input type="radio" id="sputum-large" name="sputum" value="large" class="hidden toggle-radio"/><label for="sputum-large" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-large" class="cbx-lbl">Large</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-thin" class="item"><input type="radio" id="sputum-thin" name="sputum" value="thin" class="hidden toggle-radio"/><label for="sputum-thin" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-thin" class="cbx-lbl">Thin</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-thick" class="item"><input type="radio" id="sputum-thick" name="sputum" value="thick" class="hidden toggle-radio"/><label for="sputum-thick" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-thick" class="cbx-lbl">Thick</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-mucoid" class="item"><input type="radio" id="sputum-mucoid" name="sputum" value="mucoid" class="hidden toggle-radio"/><label for="sputum-mucoid" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-mucoid" class="cbx-lbl">Mucoid</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-tenacious" class="item"><input type="radio" id="sputum-tenacious" name="sputum" value="tenacious" class="hidden toggle-radio"/><label for="sputum-tenacious" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-tenacious" class="cbx-lbl">Frothy Tenacious</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Temperature:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="temperature-oral" class="item"><input type="radio" id="temperature-oral" name="temperature" value="oral" class="hidden toggle-radio"/><label for="temperature-oral" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="temperature-oral" class="cbx-lbl">Oral</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="temperature-axilla" class="item"><input type="radio" id="temperature-axilla" name="temperature" value="axilla" class="hidden toggle-radio"/><label for="temperature-axilla" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="temperature-axilla" class="cbx-lbl">Axilla</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="temperature-rectal" class="item"><input type="radio" id="temperature-rectal" name="temperature" value="rectal" class="hidden toggle-radio"/><label for="temperature-rectal" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="temperature-rectal" class="cbx-lbl">Rectal</label></label></div>
                        </td>
                    </tr>
                </table>
            </form>

            <div class="signature-wrapper">
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text">
                    </div>
                    <span class="signature-text">Last Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text">
                    </div>
                    <span class="signature-text">First Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text">
                    </div>
                    <span class="signature-text">Middle Name</span>
                </div>

                <div class="signature-button">
                    <button type="submit">Add</button>
                </div>

            </div>
        </div>
    </div>
</body>
</html>