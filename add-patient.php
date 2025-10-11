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
                            <label for="date">Date:</label>
                            <input type="text" id="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="time">Time:</label>
                            <input type="text" id="time" name="time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mode-of-arrival">Mode of Arrival</label>
                        <div class="radio-group">
                            <div>
                                <input type="radio" id="wheelchair" name="mode-of-arrival" value="Wheelchair">
                                <label for="wheelchair">Wheelchair</label>
                            </div>
                            <div>
                                <input type="radio" id="stretcher" name="mode-of-arrival" value="Stretcher">
                                <label for="stretcher">Stretcher</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="instructed">Patient and Family Instructed</label>
                        <div class="radio-group">
                            <div>
                                <input type="radio" id="wardset" name="instructed" value="wardset">
                                <label for="wardset">Medication</label>
                            </div>
                            <div>
                                <input type="radio" id="medication" name="instructed" value="medication">
                                <label for="medication">Hospital Rules</label>
                            </div>
                            <div>
                                <input type="radio" id="hospital-rules" name="instructed" value="hospital-rules">
                                <label for="hospital-rules">Special Procedure</label>
                            </div>
                            <div>
                                <input type="radio" id="special" name="instructed" value="special">
                                <label for="special">Special Procedure Next of Kin</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="side-form-section">
                    <div class="form-group">
                        <label for="glasses/contact">Glasses/Contact Lenses</label>
                        <div class="radio-options">
                            <input type="radio" id="glasses-yes" name="glasses/contact" value="yes">
                            <label for="glasses-yes">Yes</label>
                            <input type="radio" id="glasses-no" name="glasses/contact" value="no">
                            <label for="glasses-no">No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="dentures">Dentures</label>
                        <div class="radio-options">
                            <input type="radio" id="dentures-yes" name="dentures" value="yes">
                            <label for="dentures-yes">Yes</label>
                            <input type="radio" id="dentures-no" name="dentures" value="no">
                            <label for="dentures-no">No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ambulatory/prosthesis">Ambulatory/Prosthesis</label>
                        <div class="radio-options">
                            <input type="radio" id="ambulatory-yes" name="ambulatory/prosthesis" value="yes">
                            <label for="ambulatory-yes">Yes</label>
                            <input type="radio" id="ambulatory-no" name="ambulatory/prosthesis" value="no">
                            <label for="ambulatory-no">No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="smoker">Smoker</label>
                        <div class="radio-options">
                            <input type="radio" id="smoker-yes" name="smoker" value="yes">
                            <label for="smoker-yes">Yes</label>
                            <input type="radio" id="smoker-no" name="smoker" value="no">
                            <label for="smoker-no">No</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="drinker">Drinker</label>
                        <div class="radio-options">
                            <input type="radio" id="drinker-yes" name="drinker" value="yes">
                            <label for="drinker-yes">Yes</label>
                            <input type="radio" id="drinker-no" name="drinker" value="no">
                            <label for="drinker-no">No</label>
                        </div>
                    </div>
                </div>
            </form>
            <div class="header-section">
                <h3>NURSING HISTORY</h3> <!--Centered-->
            </div>
        </div>
    </div>
</body>
</html>