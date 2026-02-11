import requests

def get_book_info(book_id):
    url = f"https://localhost/learnandhelp/api/get_book_info.php?id={book_id}"
    headers = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
  }
    try:
        response = requests.get(url, verify=False)
        if response.status_code == 200:
            data = response.json()
            return data
        else:
            print(f"Failed to fetch book info for book_id {book_id}. Status code: {response.status_code}")
    except Exception as e:
        print(f"Failed to fetch book info for book_id {book_id}. Error: {str(e)}")
    return None

# Calling the function to get book information
book_id = 1377
book_info = get_book_info(book_id)
if book_info:
    print(f"Book Title: {book_info['title']}")
    print(f"Author: {book_info['author']}")
    print(f"Publisher: {book_info['publisher']}")
    print(f"Grade Level: {book_info['grade_level']}")
else:
    print("Book information not found.")
