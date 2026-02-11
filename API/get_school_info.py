import requests

def get_school_info(school_id):
    url = f"https://localhost/learnandhelp/api/get_school_info.php?id={school_id}"
    headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
  }
    try:
        response = requests.get(url, verify=False)
        if response.status_code == 200:
            data = response.json()
            return data
        else:
            print(f"Failed to fetch school info for school_id {school_id}. Status code: {response.status_code}")
    except Exception as e:
        print(f"Failed to fetch school info for school_id {school_id}. Error: {str(e)}")
    return None\


# Calling the function to get book information
school_id = 1
school_info = get_school_info(school_id)
if school_info:
    print(f"Name: {school_info['name']}")
    print(f"Type: {school_info['type']}")
    print(f"Category: {school_info['category']}")
    print(f"State: {school_info['state_name']}")
else:
    print("school information not found.")
