function UpdateForm() 
{
    document.forms['checkout_payment'].action="index.php?main_page=checkout_payment";
    document.forms['checkout_payment'].submit();
}