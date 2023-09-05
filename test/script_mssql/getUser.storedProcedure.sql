USE [test]
GO

/****** Object:  StoredProcedure [dbo].[getUser]    Script Date: 2023.09.05. 19:39:01 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE OR ALTER PROCEDURE [dbo].[getUser]   
    @Id INT = NULL,   
	@LoginName varchar(100) = NULL
AS  
BEGIN
    SET NOCOUNT ON

	SELECT *
	FROM [dbo].[user] 
	WHERE (@Id IS NULL OR @Id = Id)
		  AND (@LoginName IS NULL OR @LoginName = LoginName)
END
GO


