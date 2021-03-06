<?php 
BMA()->load_files(BMA()->get_vars('PATH').'includes/classes/ClassesModel.php');

if(array_key_exists('add_template', $_POST)){
    $nonce = esc_attr($_POST['add_template']);
    if(! wp_verify_nonce($nonce,'add_template')){
        die( 'Go get a life script dear' );
    };
//    die(var_dump($_POST['newTemplate']));
    $class = BincomAutomatedMailsTemplates::add($_POST['newTemplate']);
    if($class){
        ?><div style="border-color:green; color:darkgreen; background-color:rgba(144,238,144,0.2);padding: 20px"
    id='setting-error-settings-success' class="updated_settings_error notice is-dismissible">
    <strong> Template  added </strong>
</div>
<?php
    }else{
        ?><div style="border-color:red; color:darkred; background-color:rgba(220,50,50,0.2); padding: 20px"
    id='setting-error-settings-success' class="updated_settings_error notice is-dismissible">
    <strong> Template not added </strong>
</div><?php
    }

}
function addInput($name, $label, $default ='' ,$required = true){
    ?>
<label for="newTemplate[<?= $name ?>]" class="label_input">
    <span style="padding:10px 2px; font-size:1.2em; color:darkblue"><?= $label ?></span>
</label>
<input value="<?php echo $default; ?>" style="margin: 5px 0px" type="text" class="large-text" name="newTemplate[<?= $name ?>]" id="" <?php if($required) {echo" required"; } ?>>

<?php
}
function addTextInput($name, $label, $default =''){
    ?>
<label for="newTemplate[<?= $name ?>]" class="label_input column-2:">
    <span style="padding:10px 0px; font-size:1.2em; color:darkblue"> <?= $label ?></span>
    <span style="margin:20px; font-size:1em;">
       <br> use this to add template feilds  <br> eg [field-name] <span style="font-size:0.9em; color:darkblue" >replace field-name with the name of feilds from your contact form</span> <br> [recipient-name]
    </span>
    <textarea style="margin: 5px 0px" rows="20" cols='50' class="large-text" name="newTemplate[<?= $name ?>]"
        id=""></textarea>
</label>
<?php
}
function addMailSelect(){
    if(array_key_exists('mail', $_REQUEST))
    {
        $mail = new BincomAutomatedMails($_REQUEST['mail'])
        ?>
            <input name="newTemplate[parent_id]" type='hidden' value=<?= $mail->id()?>>
        <?php
    }else{
        $mails = BincomAutomatedMails::find();
        ?>
        Mail: 
            <select name='newTemplate[parent_id]'>
                <option value=0>Select Mail </option> 
        <?php
        foreach($mails as $mail){
        ?>
            <option value=<?= $mail->id()?>> <?= $mail->name ?> </option>
        <?php
        }
        ?>
            </select>
        <?php
    }
}
function addSelect($name, $label , $options){
    ?>
    <div style="display:block; margin: 10px 0px" >
        <label for="newTemplate[<?= $name ?>]" class="label_input">
            <span style="padding:10px 2px; font-size:1.2em; color:darkblue"><?= $label ?></span>
        </label>
        <select name="newTemplate[<?= $name ?>]" >
    <?php
        foreach($options as $key => $value){
            ?>
                <option  value="<?= $key ?>">
                    <?= $value ?>
                </option>
            <?php
        }
    ?>
        </select>
        </div>
    <?php
}
function addCheck($name, $label){
    ?>
    <div style="display:block;">
        <label for="newTemplate[<?= $name ?>]" class="label_input">
            <span style="padding:10px 2px; font-size:1.2em; color:darkblue"><?= $label ?></span>
        </label>
        <input value="html"  style="margin: 5px 0px;" type="checkbox"name="newTemplate[<?= $name ?>]" id="" >
    </div>
    <?php
}
//TODO add use html option;
?>



<div class='wrap'>
    <h2>Add a new Tamplate</h2>

    <div class='metabox-holder'
        style="display: flex; flex-flow:row wrap; justify-content:start; background-color:rgba(255,255,255,0.2); padding:20px">
        <form method="post" class='form' style="flex: 0 0 80%; ">
           <div style='display:block; padding:10px 0px'><?php addMailSelect()?> </div>
            <?php addInput('name','Template Name') ?>
            <?php addInput('title','Input Value required',false) ?>
            <?php addInput('fields','Mail Subject', "Bincom Automated Mail") ?>
            <?php addTextInput('content','Mail Body') ?>
            <?php addCheck('status','Use Html Template') ?>
            <button type="submit" class="button button-primary" name='add_template'
                value="<?= wp_create_nonce('add_template')?>"> Add Template </button>
        </form>
    </div>
</div>

