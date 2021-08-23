private function properties()
	{
		//Проверяем свойства в инфоблоке и добавляем если не хватает
		// $this->PropDef();

		if(!CModule::IncludeModule("iblock")) return false;
		if(empty($this->iblockId)) return false;

		//Далее получаем свойства у элеметов и добавляем если не хватает
	 	$arSelect = Array('ID', 'XML_ID', 'PROPERTY_COLORS');
		$arFilter = Array("IBLOCK_ID"=>array($this->iblockId));
		$res = CIBlockElement::GetList(
		    array("ID" => "ASC"),
		    $arFilter,
		    false,
		    false,
		    $arSelect
		    );
		  
		while($ob = $res->GetNextElement())
		    {
		    $arFields = $ob->GetFields();
		    $picturesDop[$arFields['XML_ID']]['PROPERTY_COLORS'] = $arFields['PROPERTY_COLORS_VALUE'];
		    $arIdProp[$arFields['XML_ID']] = $arFields['ID'];
 		    // $arResult[$arFields['ID']] = $this->getProperty($arFields['ID']);
		    }

		$this->HLload(self::providersHL);
		$rsData = $this->strEntityDataClass::getList(array(
			'filter' => array('*'),
			'select' => array('UF_NAME', 'ID', 'UF_XML_ID'),
			'order' => array('UF_NAME' => 'DESC')
		));
			while($arItem = $rsData->Fetch()) {
				$arProv[$arItem['UF_NAME']] = $arItem['UF_XML_ID'];
			}
		
		if(empty($_SESSION['lastid']['properties'])){
			$lastId = 0;
		} else {
			$lastId = $_SESSION['lastid']['properties'];
		}
		//Тянем недостающие элементы
		$Properties = $this->send("get", "properties", $lastId);
		if(!empty($Properties)) {
			$_SESSION['lastid']['properties'] = max(array_keys($Properties));
			echo '<script>startCountdown(); function reload (){document.location.href = location.href};setTimeout("reload()", 2000);</script>';						
		} else {
			$_SESSION['lastid']['properties'] = 0;
			echo 'Все необходимые элементы добавлены и обновлены<br>';
		}

		if(!empty($Properties)){
			$countProd = 0;
			$countProp = 0;
			foreach ($Properties as $kElem => $VElem) {
				if($kElem != 'OFFERS' && $kElem != 'PRICES'){
					foreach ($VElem as $kEl => $vEl) {
						if($vEl['PROPERTY_TYPE'] == 'F' && empty($picturesDop[$kElem]['PROPERTY_COLORS_VALUE'])){
							if($vEl['MULTIPLE'] == 'Y'){
								$temp = array();
								foreach ($vEl['VALUE'] as $key => $value) {
									$DOP_PIC = $this->copyFile($value);
									$temp[] = CFile::MakeFileArray($DOP_PIC);
								}
								$PROPERTY_VALUE = $temp;
								unset($temp);
							}else{
								$PROPERTY_VALUE = CFile::MakeFileArray($this->copyFile($vEl['VALUE']));
							}
							// pp(array($PROPERTY_VALUE, $arIdProp[$kElem]));
						} elseif($vEl['PROPERTY_TYPE'] == 'L' || $vEl["CODE"] == 'SOLD'){
							$db_enum_list = CIBlockProperty::GetPropertyEnum($vEl["CODE"], array("ID"=>"ASC", "SORT"=>"ASC"), array("IBLOCK_ID"=>$this->iblockId));
								while($ar_enum_list = $db_enum_list->GetNext())
								{
									$idEnum[trim($ar_enum_list['XML_ID'])] = $ar_enum_list['ID'];
								}

							if($vEl["CODE"] == 'SOLD'){
								$PROPERTY_VALUE = array();								
								foreach ($vEl['VALUE'] as $key => $value) {
									$PROPERTY_VALUE[] = $idEnum[$value];
								}

							} elseif($vEl["CODE"] == 'PROVIDER'){
								$PROPERTY_VALUE = array();								
								foreach ($vEl['VALUE_ENUM'] as $key => $value) {
									foreach ($arProv as $kProv => $vProv) {
										if(trim($value) == trim($kProv)) $PROPERTY_VALUE[] = $vProv;
									}
									// $PROPERTY_VALUE[] = $arProv[trim($value)];
								}

								$vEl["CODE"] = 'IB_PROVIDER';

							} else {
								$PROPERTY_VALUE = array();								
								foreach ($vEl['VALUE_XML_ID'] as $key => $value) {
									$PROPERTY_VALUE[] = $idEnum[$value];
								}

							}
						} else {
							if(!empty($vEl['DESCRIPTION'])){

								$PROPERTY_VALUE = array();
								foreach ($vEl["VALUE"] as $kDES => $vDES) {
									$PROPERTY_VALUE[] = array("VALUE" => $vDES, "DESCRIPTION" => $vEl['DESCRIPTION'][$kDES]);
								}

							} else	$PROPERTY_VALUE = $vEl["VALUE"];
						}

						$PROPERTY_CODE = $vEl["CODE"];

						CIBlockElement::SetPropertyValuesEx($arIdProp[$kElem], false, array($PROPERTY_CODE => $PROPERTY_VALUE));
						$countProp++;
					}
					$countProd++;
				}
			}
