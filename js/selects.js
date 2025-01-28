function get_subcategory(val) {
    $.ajax({
    type: "POST",
    url: "get_subcategory.php",
    data:'category_id='+val,
    success: function(data){
        $("#id_subcategory").html(data);
    }
    });
}

function get_semester(val) {
    $.ajax({
    type: "POST",
    url: "get_semester.php",
    data:'subcategory='+val,
    success: function(data){
        $("#id_semester").html(data);
    }
    });
}

function get_program(val) {
    $.ajax({
    type: "POST",
    url: "get_program.php",
    data:'subcategory='+val,
    success: function(data){
        $("#id_program").html(data);
    }
    });
}
function get_course(val) {
    $.ajax({
    type: "POST",
    url: "get_course.php",
    data:'category_id='+val,
    success: function(data){
        $("#id_course").html(data);
    }
    });
}
function get_student(val) {
    $.ajax({
    type: "POST",
    url: "get_student.php",
    data:'course_id='+val,
    success: function(data){
        $("#id_student").html(data);
    }
    });
}