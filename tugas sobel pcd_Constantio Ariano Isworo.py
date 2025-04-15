import cv2
import numpy as np
import matplotlib.pyplot as plt

# Load image
def load_image(image_path):
    image = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
    return image

# Apply Sobel filter
def apply_sobel(image):
    sobel_x = cv2.Sobel(image, cv2.CV_64F, 1, 0, ksize=3)
    sobel_y = cv2.Sobel(image, cv2.CV_64F, 0, 1, ksize=3)
    sobel = cv2.magnitude(sobel_x, sobel_y)
    sobel = np.uint8(sobel)
    return sobel

# Display images
def display_images(original, sobel):
    plt.figure(figsize=(10, 5))
    plt.subplot(1, 2, 1)
    plt.imshow(original, cmap='gray')
    plt.title("Original Image")
    plt.axis("off")
    
    plt.subplot(1, 2, 2)
    plt.imshow(sobel, cmap='gray')
    plt.title("Sobel Edge Detection")
    plt.axis("off")
    
    plt.show()

# Main function
def main():
    image_path = 'c:\Users\ASUS\OneDrive\Documents\tanda_tangan.jpg'  # Ganti dengan path gambar tanda tangan
    image = load_image(image_path)
    if image is None:
        print("Error: Gambar tidak ditemukan!")
        return
    
    sobel_image = apply_sobel(image)
    display_images(image, sobel_image)

if __name__ == "__main__":
    main()
