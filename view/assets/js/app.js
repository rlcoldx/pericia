function show_pass(id){
    var type =  $('#'+id+'').attr('type');
    if(type == 'password'){
        $('#'+id+'').attr('type', 'text');
    }else{
        $('#'+id+'').attr('type', 'password');
    }
};