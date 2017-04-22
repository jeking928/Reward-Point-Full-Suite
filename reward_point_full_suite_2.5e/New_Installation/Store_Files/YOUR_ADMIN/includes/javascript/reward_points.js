<script type="text/javascript">
var Backup="";

function UpdateStateList()
{
	var stateform=document.forms["configuration"];
	var pend_list=new Array();
	var earn_list=new Array();
	
	for(loop=0;loop<stateform.length;loop++)
	 if(stateform.elements[loop].type=="radio" && stateform.elements[loop].checked)
	  switch(stateform.elements[loop].value)
	  {
		case "0":
			pend_list.push(stateform.elements[loop].id);
			break;
			
		case "1":
			earn_list.push(stateform.elements[loop].id);
			break;
	  }
			
	document.forms["configuration"]["configuration_value"].value=pend_list.join(",")+"/"+earn_list.join(",");
}

function UpdateMode()
{
	if(document.forms["configuration"]["mode_id"].value==0)
	{
		Backup=document.forms["configuration"]["configuration_value"].value;
		document.forms["configuration"]["configuration_value"].value='';
		HideDisplayAdvancedModeTable(false);
	}
	else
	{
		document.forms["configuration"]["configuration_value"].value=Backup;
		HideDisplayAdvancedModeTable(true);
	}
}

function UpdateAward()
{
	if(document.forms["configuration"]["allow_award"].checked)
	{
		if(document.forms["configuration"]["award_id"].value=='0')
		 document.forms["configuration"]["configuration_value"].value=document.forms["configuration"]["award_points"].value*-1;
		else
		 document.forms["configuration"]["configuration_value"].value=document.forms["configuration"]["award_points"].value;
	}
	else
	{
		document.forms["configuration"]["configuration_value"].value=0;
	}
}

function HideDisplayAdvancedModeTable(state)
{
	if (document.getElementById)
	 if(state)
      document.getElementById("AdvancedModeTable").style.display="block";
	 else
      document.getElementById("AdvancedModeTable").style.display="none";
}

function SetCheckboxes(formname,prefix,count,state)
{
	for(var loop=0;loop<count;loop++) 
	{
		box=eval("document."+formname+"."+prefix+j); 
		if(box.checked!=state)
		 box.checked=state;
    }
}

function SetDiscountTableFields(row)
{
	if(document.getElementById)
	{
		document.getElementById("discountField").value=parseInt(row.cells[0].textContent);
		document.getElementById("requiredField").value=parseInt(row.cells[1].textContent);
		document.getElementById("discountField").focus();
	}
}

function AddOrUpdateDiscountRecord()
{
	if(document.getElementById)
	 if((DiscountTable=document.getElementById("RewardPointDiscountTable"))!=null)
	  if((DiscountTableRows=DiscountTable.rows)!=null)
	   {
			var CurrentDiscount=parseInt(document.getElementById("discountField").value);
			if(CurrentDiscount<=0 || (CurrentDiscount>100 && document.forms["modules"]["configuration[MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE]"].value=='0'))
			{
				alert("Error: Incorrect value set for Discount");
				document.getElementById("discountField").focus();
				return;
			}

			var CurrentRequired=parseInt(document.getElementById("requiredField").value);
			if(CurrentRequired<=0)
			{
				alert("Error: Incorrect value set for Points Required");
				document.getElementById("requiredField").focus();
				return;
			}

			for(loop=1;loop<DiscountTableRows.length;loop++) // Start at 1 to skip header row and end before Add/Confirm row.
			 if(loop==DiscountTableRows.length-1 || CurrentDiscount<parseInt(DiscountTableRows[loop].cells[0].textContent))
			 {
				if(loop<DiscountTableRows.length && CurrentRequired>parseInt(DiscountTableRows[loop].cells[1].textContent))
				{
					alert("Error: Cannot set discount as required points are greater than the next tier!");
					document.getElementById("requiredField").focus();
					return;
				}
				 
				if(loop>1 && CurrentRequired<parseInt(DiscountTableRows[loop-1].cells[1].textContent))
				{
					alert("Error: Cannot set discount as required points are lower than the previous tier!");
					document.getElementById("requiredField").focus();
					return;
				}
					
				newRow=DiscountTable.insertRow(loop);
				newRow.className="dataTableRow";
				newRow.setAttribute("onclick","SetDiscountTableFields(this)");
				newRow.setAttribute("onmouseout","rowOutEffect(this)");
				newRow.setAttribute("onmouseover","rowOverEffect(this)");
				
				newDiscountCell=newRow.insertCell(0);
				newDiscountCell.textContent=CurrentDiscount;
				newRequiredCell=newRow.insertCell(1);
				newRequiredCell.textContent=CurrentRequired;
				newIconCell=newRow.insertCell(2);
				newIconCell.innerHTML='<img border="0" title=" Delete " alt="Delete" src="images/icon_rp_delete.gif" onclick="DeleteDiscountRecord(this)"/>';

				break;
			 }
			 else
			  if(CurrentDiscount==parseInt(DiscountTableRows[loop].cells[0].textContent))
			  {
				DiscountTableRows[loop].cells[1].textContent=CurrentRequired;
				break;
			  }
			RefreshDiscountTableConfigurationValue();
	   }
}

function DeleteDiscountRecord(img)
{
	if(document.getElementById)
	 if((DiscountTable=document.getElementById("RewardPointDiscountTable"))!=null)
	 {
		DiscountTable.deleteRow(img.parentNode.parentNode.rowIndex);
		RefreshDiscountTableConfigurationValue();
	 }
}

function RefreshDiscountTableConfigurationValue()
{
	var discount_list=new Array();
	
	if(document.getElementById)
	 if((DiscountTable=document.getElementById("RewardPointDiscountTable"))!=null)
	  if((DiscountTableRows=DiscountTable.rows)!=null)
	   for(loop=1;loop<DiscountTableRows.length-1;loop++) // Start at 1 to skip header row and end before Add/Confirm row.
        discount_list.push(parseInt(DiscountTableRows[loop].cells[0].textContent)+":"+parseInt(DiscountTableRows[loop].cells[1].textContent));

	document.forms["modules"]["configuration[MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE]"].value=discount_list.join(",");
}

function AddAdvancedCalculateRecord()
{
	if(document.getElementById)
	 if((ModuleTable=document.getElementById("RewardPointAdvancedCalculateTable"))!=null)
	  if((ModuleTableRows=ModuleTable.rows)!=null)
	  {
			for(loop=1;loop<ModuleTableRows.length;loop++)
			 if(ModuleTableRows[loop].cells[0].textContent==document.getElementsByName("moduleField")[0].value)
			 {
				ModuleTable.deleteRow(loop);
				break;
			 }
			  
			newRow=ModuleTable.insertRow(ModuleTableRows.length-1);
			newRow.className="dataTableRow";
			newRow.setAttribute("onmouseout","rowOutEffect(this)");
			newRow.setAttribute("onmouseover","rowOverEffect(this)");
				
			newModuleCell=newRow.insertCell(0);
			newModuleCell.textContent=document.getElementsByName("moduleField")[0].value;
			newActionCell=newRow.insertCell(1);
			newActionCell.textContent=document.getElementsByName("actionField")[0].value;
			newIconCell=newRow.insertCell(2);
			newIconCell.innerHTML='<img border="0" title=" Delete " alt="Delete" src="images/icon_rp_delete.gif" onclick="DeleteAdvancedCalculateRecord(this)"/>';
			
			RefreshAdvancedCalculateTableConfigurationValue();
	  }
}

function DeleteAdvancedCalculateRecord(img)
{
	if(document.getElementById)
	 if((AdvancedCalculateTable=document.getElementById("RewardPointAdvancedCalculateTable"))!=null)
	 {
		AdvancedCalculateTable.deleteRow(img.parentNode.parentNode.rowIndex);
		RefreshAdvancedCalculateTableConfigurationValue();
	 }
}

function RefreshAdvancedCalculateTableConfigurationValue()
{
	var module_list=new Array();
	
	if(document.getElementById)
	 if((ModuleTable=document.getElementById("RewardPointAdvancedCalculateTable"))!=null)
	  if((ModuleTableRows=ModuleTable.rows)!=null)
	   for(loop=1;loop<ModuleTableRows.length-1;loop++) // Start at 1 to skip header row and end before Add/Confirm row.
        module_list.push((ModuleTableRows[loop].cells[1].textContent=="Add"?"+":"-")+ModuleTableRows[loop].cells[0].textContent);

	document.forms["configuration"]["configuration_value"].value=module_list.join(",");
}
</script>