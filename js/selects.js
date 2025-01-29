//correcion js 
function get_program(val) {
    $.ajax({
    type: "POST",
    url: "get_data.php",
    data:'flag=category&categoryid='+val,
    success: function(data){
        $("#id_program").html(data);
    }
    });
}

function get_semester(val) {
    $.ajax({
    type: "POST",
    url: "get_data.php",
    data:'flag=category&categoryid='+val,
    success: function(data){
        $("#id_semester").html(data);
    }
    });
}
function get_course(val) {
    $.ajax({
    type: "POST",
    url: "get_data.php",
    data:'flag=course&categoryid='+val,
    success: function(data){
        $("#id_course").html(data);
    }
    });
}
function get_student(val) {
    $.ajax({
    type: "POST",
    url: "get_data.php",
    data:'flag=user&roleid=5&courseid='+val,
    success: function(data){
        $("#id_student").html(data);
    }
    });
}