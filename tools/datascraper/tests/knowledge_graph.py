from selenium import webdriver
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as ec

import json
import requests

unsearchable = []
database = []

driver = webdriver.Firefox()
driver.get("http://google.cz")
assert "Google" in driver.title

def get_knowledge_graph_info(array, id=None):
    global unsearchable, driver, database

    if id is None:
        id = 0

    searchbox = driver.find_element_by_xpath('//*[@id="gbqfq"]')
    searchbox.clear()
    searchbox.send_keys(name)
    searchbox.send_keys(Keys.RETURN)

    address = None
    coords = None


    try:
        graph = WebDriverWait(driver, 4).until(ec.presence_of_element_located((By.ID, 'rhs')))

        key = graph.find_element_by_xpath("//span[text()[contains(.,'Adresa')] and @style='font-weight:bold']/..")

        derp = key.find_elements_by_tag_name("span")

        if len(derp) == 2 and "Adresa" in derp[0].text:
            address = derp[1].text
            coords = require_coords(derp[1].text)
    except:
        unsearchable.append(name)

    dict = {"name": name, "coords": coords, "address": address}

    database.append(dict)
    pretty_print(dict)


def pretty_print(dict):
    print("Název: "+dict["name"])
    if not dict["address"] is None:
        print("Adresa: "+dict["address"])
    if not dict['coords'] is None:
        print("Souřadnice: "+"Lat: "+str(dict["coords"]["lat"])+", Lng: "+str(dict["coords"]["lng"]))
    print("-"*30)


def require_coords(address):
    rq = requests.get("http://maps.googleapis.com/maps/api/geocode/json", params={
        "address": address,
        "sensor": "true",
        "region": "cz"
    }, verify=False)

    data = json.loads(rq.text)

    if data['status'] == "OK" and len(data["results"]) > 0:
        return data["results"][0]['geometry']['location']

    return None


get_knowledge_graph_info("Střední škola grafická Brno")