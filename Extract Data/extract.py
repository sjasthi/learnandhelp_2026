import csv

# Read the unstructured data from the text file and split lines
with open('/Applications/XAMPP/xamppfiles/htdocs/learnandhelp/Extract Data/unstructured_school_data.txt', 'r') as file:  
    lines = file.readlines()

# Define the CSV columns
csv_columns = [
    'id', 'name', 'type', 'category', 'grade_level_start', 'grade_level_end', 'current_enrollment',
    'address_text', 'state_name', 'state_code', 'pin_code', 'contact_name', 'contact_designation',
    'contact_phone', 'contact_email', 'status', 'notes', 'referenced_by', 'supported_by',
]

# Initialize an empty list to store the extracted information
extracted_data = []

# Process each line and extract data into the list
for line in lines:
    data = line.strip().split(',')
    # Remove extra quotation marks from the extracted fields
    cleaned_data = [field.strip('""') for field in data]
    extracted_data.append({
        'id': cleaned_data[0],
        'name': cleaned_data[1],
        'type': '',
        'category': '',
        'grade_level_start': '',
        'grade_level_end': '',
        'current_enrollment': '',
        'address_text': cleaned_data[4],
        'state_name': '',
        'state_code': '',
        'pin_code': '',
        'contact_name': cleaned_data[2],
        'contact_designation': '',
        'contact_phone': cleaned_data[3],
        'contact_email': '',
        'status': '',
        'notes': '',
        'referenced_by': '',
        'supported_by': 'PGNF',
    })

# Write extracted information to a CSV file
csv_file = "extracted_school_data.csv"

try:
    with open(csv_file, 'w', newline='', encoding='utf-8') as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=csv_columns)
        writer.writeheader()
        for school in extracted_data:
            writer.writerow(school)
    print(f"Extracted data has been written to {csv_file}")
except IOError:
    print("Error writing to CSV file")
