# Use an official Node.js runtime as a parent image
FROM node:20-alpine

# Set the working directory in the container
WORKDIR /app

# Copy package.json and package-lock.json to leverage Docker cache
COPY package*.json ./

# Install application dependencies
RUN npm install

# Copy the rest of the application's source code into the container
COPY . .

# Expose the port Vite runs on
EXPOSE 5173

# The command to run the development server
CMD ["npm", "run", "dev"]
