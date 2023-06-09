﻿IF NOT EXISTS(SELECT * FROM sys.databases WHERE name = 'test')
BEGIN
	CREATE DATABASE [test]
END

GO
USE [test]

GO
/****** Object:  Table [dbo].[user_userrolescollection_usersettingscollection]    Script Date: 2023.06.22. 23:37:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
DROP TABLE IF EXISTS [dbo].[user_userrolescollection_usersettingscollection]
CREATE TABLE [dbo].[user_userrolescollection_usersettingscollection](
	[Id] [int] IDENTITY(1,1) NOT NULL,
	[User_UserRolesCollection] [int] NOT NULL,
	[UserSetting] [int] NOT NULL,
	[Value] [varchar](100) NULL,
	[IsDeleted] [tinyint] NOT NULL,
 CONSTRAINT [PK__user_use__3214EC0779CD1A8E] PRIMARY KEY CLUSTERED 
(
	[Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO
/****** Object:  Table [dbo].[user_userrolescollection]    Script Date: 2023.06.22. 23:37:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
DROP TABLE IF EXISTS [dbo].[user_userrolescollection]
CREATE TABLE [dbo].[user_userrolescollection](
	[Id] [int] IDENTITY(1,1) NOT NULL,
	[User] [int] NOT NULL,
	[UserRole] [int] NOT NULL,
	[IsDeleted] [tinyint] NOT NULL,
 CONSTRAINT [PK__user_use__3214EC07139C4111] PRIMARY KEY CLUSTERED 
(
	[Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO
/****** Object:  Table [dbo].[user]    Script Date: 2023.06.22. 23:37:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
DROP TABLE IF EXISTS [dbo].[user]
CREATE TABLE [dbo].[user](
	[Id] [int] IDENTITY(1,1) NOT NULL,
	[LoginName] [varchar](100) NULL,
	[Password] [varchar](100) NULL,
	[LastLoginDateTime] [datetime] NULL,
	[IsLogged] [tinyint] NOT NULL,
	[IsDeleted] [tinyint] NOT NULL,
	[DefaultUserRole] [int] NULL,
 CONSTRAINT [PK_user] PRIMARY KEY CLUSTERED 
(
	[Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[userrole]    Script Date: 2023.06.22. 23:37:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
DROP TABLE IF EXISTS [dbo].[userrole]
CREATE TABLE [dbo].[userrole](
	[Id] [int] IDENTITY(1,1) NOT NULL,
	[Code] [varchar](50) NOT NULL,
	[Name] [varchar](100) NOT NULL,
	[IsDeleted] [tinyint] NOT NULL,
 CONSTRAINT [PK__userrole__3214EC07DE834AB3] PRIMARY KEY CLUSTERED 
(
	[Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO
/****** Object:  Table [dbo].[usersetting]    Script Date: 2023.06.22. 23:37:33 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
DROP TABLE IF EXISTS  [dbo].[usersetting]
CREATE TABLE [dbo].[usersetting](
	[Id] [int] IDENTITY(1,1) NOT NULL,
	[Name] [varchar](100) NOT NULL,
	[DefaultValue] [varchar](100) NULL,
	[IsDeleted] [tinyint] NOT NULL,
 CONSTRAINT [PK__usersett__3214EC07E74868B6] PRIMARY KEY CLUSTERED 
(
	[Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO
ALTER TABLE [dbo].[user]  WITH CHECK ADD  CONSTRAINT [FK_DefaultUserRole] FOREIGN KEY([DefaultUserRole])
REFERENCES [dbo].[userrole] ([Id])
GO
ALTER TABLE [dbo].[user] CHECK CONSTRAINT [FK_DefaultUserRole]
GO
ALTER TABLE [dbo].[user_userrolescollection]  WITH CHECK ADD  CONSTRAINT [FK_User] FOREIGN KEY([User])
REFERENCES [dbo].[user] ([Id])
GO
ALTER TABLE [dbo].[user_userrolescollection] CHECK CONSTRAINT [FK_User]
GO
ALTER TABLE [dbo].[user_userrolescollection]  WITH CHECK ADD  CONSTRAINT [FK_UserRole] FOREIGN KEY([UserRole])
REFERENCES [dbo].[userrole] ([Id])
GO
ALTER TABLE [dbo].[user_userrolescollection] CHECK CONSTRAINT [FK_UserRole]
GO
ALTER TABLE [dbo].[user_userrolescollection_usersettingscollection]  WITH CHECK ADD  CONSTRAINT [FK_User_UserRolesCollection] FOREIGN KEY([User_UserRolesCollection])
REFERENCES [dbo].[user_userrolescollection] ([Id])
GO
ALTER TABLE [dbo].[user_userrolescollection_usersettingscollection] CHECK CONSTRAINT [FK_User_UserRolesCollection]
GO
ALTER TABLE [dbo].[user_userrolescollection_usersettingscollection]  WITH CHECK ADD  CONSTRAINT [FK_UserSetting] FOREIGN KEY([UserSetting])
REFERENCES [dbo].[usersetting] ([Id])
GO
ALTER TABLE [dbo].[user_userrolescollection_usersettingscollection] CHECK CONSTRAINT [FK_UserSetting]
GO