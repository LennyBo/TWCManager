$(function(){
    updatePage();
    setInterval(updatePage, 2000);

     $('#btnBoost').click(function() {
        window.location.href='/TWCapi.php?Boost=1';
     });

     $('#btnEco').click(function() {
        window.location.href='/TWCapi.php?CancelBoost=1';
     });
});

function updatePage(){
    $.getJSON( "TWCapi.php?GetStatus=1", function( data,status ) {
        if(status == "success"){
            if(data.chargeNowAmps > 0){
                $("#mode").text("Mode BOOST");
                $("#btnEco").removeClass('hidden');
                $("#btnBoost").addClass('hidden');
            }else{
                $("#mode").text("Mode ECO");
                $("#btnEco").addClass('hidden');
                $("#btnBoost").removeClass('hidden');
            }
        }

        $("#available").text(data.maxAmpsToDivideAmongSlaves + " A");
        $("#pulling").text(data.TWC[0].twcChargeSpeed + " A");
    });
}