# Use the official Node.js 16 image from Docker Hub
FROM node:slim

# Set the working directory to /app
WORKDIR /app

# Copy the current directory contents into the Docker image
COPY . /app

# Install any needed packages specified in package.json
RUN npm install

# Make port 3000 available to the outside of the Docker container
EXPOSE 3000

# Run server.js when the container launches
CMD ["node", "server.js"]
