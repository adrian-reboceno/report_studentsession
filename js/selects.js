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
function get_users(val) {
    $.ajax({
    type: "POST",
    url: "get_data.php",
    data:'flag=user&role=student&courseid='+val,
    success: function(data){
        $("#id_student").html(data);
    }
    });
}